<?php

/**
 * @file
 * Theme for WissKI Mirador views.
 */
function template_preprocess_views_view_wisskimirador(&$variables) {
  // View options set by user.
  $options = $variables['view']->style_plugin->options;

  // Update options for twig.
  $variables['options'] = $options;

}