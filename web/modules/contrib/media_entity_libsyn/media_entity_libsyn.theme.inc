<?php

/**
 * @file
 * Theme functions for the media_entity_libsyn module.
 */

/**
 * Prepares variables for media_entity_libsyn templates.
 *
 * Default template: media-libsyn-embed.html.twig.
 */
function template_preprocess_media_libsyn_embed(&$variables) {
  // Build the embed url.
  $url = 'https://html5-player.libsyn.com/embed/episode/id/' . $variables['episode_id'];

  foreach ([
    'embed_theme' => 'theme',
    'direction' => 'direction',
    'custom_color' => 'custom-color',
  ] as $key => $slug) {
    if (isset($variables[$key]) && !empty($variables[$key])) {
      $url .= '/' . $slug . '/' . $variables[$key];
    }
  }

  // Add options.
  foreach ($variables['options'] as $option => $value) {
    $slug = $option;
    if ($option == 'render_playlist') {
      $slug = 'render-playlist';
    }
    $url .= '/' . $slug . '/' . ($value ? 'yes' : 'no');
  }

  $variables['url'] = $url;

  $variables['width'] = _media_entity_libsyn_dimension_validate($variables['width']);
  $variables['height'] = _media_entity_libsyn_dimension_validate($variables['height']);
}

/**
 * Helper function to validate iFrame dimensions.
 *
 * Checks for units of measure on dimension string.
 * Applies `px` unit of measure, if none is present.
 *
 * @param string $dimension
 *   Width or height of iFrame.
 *
 * @return string
 *   Validated version of dimension.
 */
function _media_entity_libsyn_dimension_validate($dimension) {
  // If dimension is only a number, then append `px`.
  if (!empty($dimension) && is_numeric($dimension)) {
    $dimension .= 'px';
  }
  return $dimension;
}
