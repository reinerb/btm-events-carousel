<?php
/*
Plugin Name:  BTM Post Display
Description:  A carousel to display upcoming events at BTM.
Version:      1.0
Author:       Benjamin Reiner
Author URI:   https://github.com/reinerb/
*/

// GitHub updater
include_once('updater.php');

if (is_admin()) {
  $config = array(
			'slug' => plugin_basename(__FILE__),
			'proper_folder_name' => 'btm-events-carousel',
			'api_url' => 'https://api.github.com/repos/reinerb/btm-events-carousel',
			'raw_url' => 'https://github.com/reinerb/btm-events-carousel/raw/main/',
			'github_url' => 'https://github.com/reinerb/btm-events-carousel',
			'zip_url' => 'https://github.com/reinerb/btm-events-carousel/zipball/main',
			'sslverify' => false,
			'requires' => '6.0',
			'tested' => '6.2.2',
			'readme' => 'README.md',
		);

  new WP_GitHub_Updater($config);
}

// Add stylesheets, scripts, shortcodes
function enqueue_events_carousel_scripts () {
  wp_enqueue_style('btm_post_card_display', plugins_url('/css/post-card.css', __FILE__));
  wp_enqueue_script('splide_script', plugins_url('/js/splide.min.js', __FILE__));
  wp_enqueue_style('events_splide_main', plugins_url('/css/splide.min.css', __FILE__));
  wp_enqueue_style('events_splide_mods', plugins_url('/css/splide-mods.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'enqueue_events_carousel_scripts');

add_shortcode('eventscarousel', 'create_events_carousel');
add_shortcode('newscards', 'create_news_cards');

// Query upcoming events posts
function find_event_posts (int $numberposts = 8) {
  $now = date('Y-m-d H:i:s');

  $query_params = [
    'category_name' => 'events',
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
    throw new Exception('No posts in ' . $query_params['category']);
  }
}

// Query BTM News posts
function find_news_posts (int $numberposts = 4) {
  $now = date('Y-m-d H:i:s');

  $query_params = [
    'category_name' => 'news',
    'numberposts' => $numberposts,
    'order' => 'ASC',
    'orderby' => 'date',
  ];

  $query = new WP_Query($query_params);

  if ($query->have_posts()) {
    return $query->posts;
  } else {
    throw new Exception('No posts in ' . $query_params['category']);
  }
}

// Make card for an event post
function make_events_post_card (
  string $image_tag, 
  string $title, 
  DateTime $date, 
  string $excerpt, 
  string $post_url
) {
  $rendered_date = $date->format('l, F j, Y');
  $rendered_time = $date->format('g:i a');

  return 
  "<div class='post-card'>
    $image_tag
    <div class='post-card__content'>
      <h3 class='post-card__title'>$title</h3>
      <div class='post-card__datetime'>
        <p>$rendered_date</p>
        <p>$rendered_time</p>
      </div>
      <p class='post-card__excerpt'>$excerpt</p>
      <a class='post-card__link' href='$post_url'>Read more</a>
    </div>
  </div>";
}

// Make card for a news post
function make_news_post_card (
  string $image_tag,
  string $title,
  string $post_url
) {
  return 
  "<div class='post-card'>
    $image_tag
    <div class='post-card__content'>
      <h3 class='post-card__title'>$title</h3>
      <a class='post-card__link' href='$post_url'>Read more</a>
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
      perPage: 1,
      breakpoints: {
        1500: {
          perPage: 4,
        },
        1200: {
          perPage: 3,
          gap: '2rem',
        },
        800: {
          perPage: 2,
          gap: '1rem'
        }
      }
    });
    splide.mount();
  </script>";

  return $carousel;
}

// Create the current news display
function create_news_display(array $elements) {
  function append_all ($carry, $item) {
    $carry = $carry . $item;
    return $carry;
  }

  $elements_as_string = array_reduce($elements, 'append_all', '');

  $news_display = 
  "<div class='news-posts'>
    $elements_as_string
  </div>";

  return $news_display;
}

// Render events carousel at [eventscarousel] shortcode
function create_events_carousel ($atts) {
  $sc_atts = shortcode_atts([
    'number_of_posts' => 8,
    'carousel_id' => 'upcoming_events'
  ], $atts);

  // Get all posts
  try {
    $posts = find_event_posts($sc_atts['number_of_posts']);
  } catch (Exception $e) {
    return "<p>Sorry, we don't have any upcoming events to list right now. Check back later.</p>";
  }

  // Make array of cards from post data
  $post_cards = array();
  foreach ($posts as $post){
    if (strlen($post->post_title) <= 30) {
      $title = $post->post_title;
    } else {
      $title = substr($post->post_title, 0, 27) . '...';
    };
    $image_tag = get_the_post_thumbnail($post, 'full', ['class' => '.post-card__image', 'alt' => "The featured image for $title."]);
    $permalink = get_permalink($post->ID);
    $event_date = get_post_meta($post->ID, 'event_date', true);
    $rawExcerpt = get_the_excerpt($post);
    if (strlen($rawExcerpt) <= 60) {
      $excerpt = $rawExcerpt;
    } else {
      $excerpt = substr($rawExcerpt, 0, 57) . '...';
    }
    
    $post_cards[] = 
    make_events_post_card(
      $image_tag,
      $title,
      new DateTime($event_date),
      $excerpt,
      $permalink
    ); 
  }

  return create_splide_carousel($post_cards, $sc_atts['carousel_id']);
}

// Render news cards at [newscards] shortcode
function create_news_cards($atts) {
  $sc_atts = shortcode_atts([
    'number_of_posts' => 4,
  ], $atts);

  // Find relevant posts
  try {
    $posts = find_news_posts($sc_atts['number_of_posts']);
  } catch (Exception $e) {
    return "<p>Sorry, we don't have any news items to list right now. Check back later.</p>";
  }

  // Create an array of post cards
  $post_cards = array();
  foreach($posts as $post) {
    if (strlen($post->post_title) <= 35) {
      $title = $post->post_title;
    } else {
      $title = substr($post->post_title, 0, 35) . '...';
    };
    $image_tag = get_the_post_thumbnail($post, 'full', ['class' => '.post-card__image', 'alt' => "The featured image for $title."]);
    $permalink = get_permalink($post->ID);

    $post_cards[] = make_news_post_card($image_tag, $title, $permalink);
  }

  return create_news_display($post_cards);
}
?>