<?php
/**
 * @file
 */

/**
 * Implements hook_views_plugins_argument_default_alter().
 */
function views_default_user_taxonomy_views_plugins_argument_default_alter(&$plugins) {
  $plugins['taxonomy_tid']['class'] = 'Drupal\views_default_user_taxonomy\Plugin\views\argument_default\Tid';
}
