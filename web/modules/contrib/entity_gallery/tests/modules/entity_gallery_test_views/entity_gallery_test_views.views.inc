<?php

/**
 * @file
 * Provides views data and hooks for entity_gallery_test_views module.
 */

/**
 * Implements hook_views_data_alter().
 */
function entity_gallery_test_views_views_data_alter(array &$data) {
  // Make entity gallery language use the basic field handler if requested.
  if (\Drupal::state()->get('entity_gallery_test_views.use_basic_handler')) {
    $data['entity_gallery_field_data']['langcode']['field']['id'] = 'language';
  }
}
