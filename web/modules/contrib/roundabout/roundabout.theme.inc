<?php

/**
 * @file
 * Template function for roundabout style plugin.
 */

/**
 * Implements template_preprocess_views_view().
 */
function template_preprocess_views_view_roundabout(&$variables) {
  // Inherit the normal unformatted classes.
  template_preprocess_views_view_unformatted($variables);
}
