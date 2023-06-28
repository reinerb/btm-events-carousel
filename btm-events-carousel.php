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
function find_event_posts (int|string $category, int $numberposts = 8) {
  $now = date('Y-m-d H:i:s');

  return get_posts([
    'category' => $category,
    'numberposts' => $numberposts,
    'order' => 'DESC',
    'orderby' => 'meta_value',
    'meta_query' => [[
      'key' => 'Event Date',
      'value' => $now,
      'type' => 'DATETIME',
      'compare' => '>='
    ]]
  ]);
}

// Make card
function make_post_card (string $featured_image_url, string $title, DateTime $date, string $excerpt, string $post_url) {
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

// Render cards at shortcode
function create_events_carousel ($atts) {
  $postData = get_post_meta($post_id = 166, $key = 'Event Date', $single = true);

  return '<pre>' . $postData . '</pre>';
}

?>