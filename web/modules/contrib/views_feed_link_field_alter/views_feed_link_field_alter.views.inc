<?php

/**
 * @file
 * Contains views_feed_link_field_alter.views.inc.
 */

/**
 * Implements hook_views_plugins_row_alter().
 */
function views_feed_link_field_alter_views_plugins_row_alter(array &$plugins) {
  $plugins['rss_fields']['id'] = 'rss_fields_alter';
  $plugins['rss_fields']['class'] = 'Drupal\views_feed_link_field_alter\Plugin\views\row\RssFieldsAlter';
}
