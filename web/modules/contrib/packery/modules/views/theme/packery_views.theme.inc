<?php

/**
 * @file
 * packery_views.theme.inc
 */

/**
 * Template preprocess Packery Views variables.
 */
function template_preprocess_packery_views(&$variables) {
  if (!empty($variables['view'])) {

    $items = array();
    foreach ($variables['rows'] as $id => $row) {
      $items[] = array(
        'row' => \Drupal::service('renderer')->render($row)
      );
    }

    // Row items & instance settings.
    $variables['settings'] = $variables['view']->style_plugin->options;
    $variables['items'] = $items;
  }
}

/**
 * Theme declaration for Packery Views.
 */
function theme_packery_views($variables) {
  $output = array(
    '#theme' => 'packery',
    '#items' => $variables['items'],
    '#settings' => $variables['settings']['settings']
  );

  return drupal_render($output);
}
