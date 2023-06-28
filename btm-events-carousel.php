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

  return $query->posts;
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
  $postData = get_post_meta($post_id = 166, $key = 'Event Date', $single = true);

  return '<pre>' . $postData . '</pre>';
}

?>