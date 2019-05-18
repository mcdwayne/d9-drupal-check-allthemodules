<?php

/**
 * Implements template_preprocess().
 *
 * Formats variables and sanitizes data needed to build the like button.
 */
function template_preprocess_fblikebutton(&$variables) {
  $url = urlencode($variables['url']);
  
  // Ensure we translate the boolean into a string for the url to pass to
  // facebook.
  $show_faces = $variables['show_faces'] ? 'true' : 'false';
}
