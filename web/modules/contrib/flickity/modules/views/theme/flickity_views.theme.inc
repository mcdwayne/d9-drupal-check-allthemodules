<?php

/**
 * @file
 * flickity_views.theme.inc
 */

/**
 * Template preprocess Flickity Views variables.
 */
function template_preprocess_flickity_views(&$variables) {
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
 * Theme declaration for Flickity Views.
 */
function theme_flickity_views($variables) {
  $output = array(
    '#theme' => 'flickity',
    '#items' => $variables['items'],
    '#settings' => $variables['settings']['settings']
  );

  return drupal_render($output);
}
