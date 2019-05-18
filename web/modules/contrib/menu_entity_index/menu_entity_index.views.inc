<?php

/**
 * @file
 * Provides views data for Menu Entity Index module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function menu_entity_index_views_data() {
  return \Drupal::service('menu_entity_index.views_data')->getViewsData();
}
