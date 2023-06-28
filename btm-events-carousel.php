<?php
/*
Plugin Name:  BTM Events Carousel
Description:  A carousel to display upcoming events at BTM.
Version:      1.0
Author:       Benjamin Reiner
Author URI:   https://github.com/reinerb/
*/

add_shortcode('eventscarousel', 'create_events_carousel');

// Query posts
function find_event_posts (int $numberposts = 8) {
  $now = date('Y-m-d H:i:s');

  $query_params =  [
    'category' => 'Upcoming Events',
    'numberposts' => $numberposts,
    'order' => 'ASC',
    'orderby' => 'meta_value',
    'meta_query' => [[
      'key' => 'event_date',
      'value' => $now,
      'type' => 'DATETIME',
      'compare' => '>='
    ]]
  ];

  $query = new WP_Query($query_params);

  if ($query->have_posts()) {
    return $query->posts;
  }
  else {
    die('No posts in category');
  }
}

// Make card
function make_post_card (
  string $featured_image_url, 
  string $title, 
  DateTime $date, 
  string $excerpt, 
  string $post_url
) {
  $rendered_date = $date->format('l, F j, Y') . ' at ' . $date->format('g:i a');

  return 
  "<div class='post-card'>
    <img src='$featured_image_url' alt='The featured image for $title'>
    <div class='post-card__content'>
      <h3 class='post-card__title'>$title</h3>
      <p class='post-card__datetime'>$rendered_date</p>
      '<p class='post-card__excerpt'>$excerpt</p>
      '<a href='$post_url'>Read more</a>
    </div>
  </div>";
}

// Create Splide carousel
function create_splide_carousel (array $elements, string $carousel_id) {
  $elements_as_list_items = "";

  foreach ($elements as $element) {
    $elements_as_list_items = $elements_as_list_items . 
    "<li class='splide__slide'>$element</li>";
  }
  
  $carousel =   
  "<div class='splide' role='group' id='$carousel_id'>
    <div class='splide__track'>
      <ul class='splide__list'>
        $elements_as_list_items
      </ul>
    </div>
  </div>
  <script>
    var splide = new Splide('#$carousel_id', {
      mediaQuery: 'min',
      focus: 0,
      omitEnd: true,
      gap: '2rem',
      fixedWidth: 'clamp(15ch, 80%, 35ch)',
    });
    splide.mount();
  </script>";

  return $carousel;
}

// Render cards at shortcode
function create_events_carousel ($atts) {
  $sc_atts = shortcode_atts([
    'number_of_posts' => 8,
    'carousel_id' => 'upcoming_events'
  ], $atts);

  // Get all posts
  $posts = find_event_posts($sc_atts['number_of_posts']);

  // Make array of cards from post data
  $post_cards = array();
  foreach ($posts as $post){
    $image_url = wp_get_attachment_image_src(get_post_thumbnail_id($post->id));
    $permalink = get_permalink($post->ID);
    $event_date = get_post_meta($post->ID, 'event_date', true);
    
    $post_cards[] = make_post_card(
      $image_url,
      $post->post_title,
      new DateTime($event_date),
      $post->excerpt,
      $permalink
    );
  }

  return create_splide_carousel($post_cards, $sc_atts['carousel_id']);
}
?>