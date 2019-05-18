<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\search_api_date_popup\SearchApiDatePopup;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function search_api_date_popup_views_plugins_filter_alter(&$info) {
  $info['search_api_date']['class'] = SearchApiDatePopup::class;
}
