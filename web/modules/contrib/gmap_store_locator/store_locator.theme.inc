<?php

/**
 * @file
 * Contains store_locator.theme.inc.
 */

use Drupal\store_locator\Helper\GoogleApiKeyHelper;
use Drupal\store_locator\Helper\LocationDataHelper;

/**
 * Custom preprocess to handle the item list and map infowindow.
 */
function template_preprocess_location_data(&$variables) {
  $config = \Drupal::config('store_locator.settings');
  $variables['empty_data'] = '';
  $variables['page_title'] = $config->get('title');
  $list_data = LocationDataHelper::loadInfowindow('list');
  if (is_array($list_data['itemlist']) && !empty($list_data['itemlist'])) {
    $location_data = LocationDataHelper::loadInfowindow('infowindow');
    $variables['location_list'] = $list_data['itemlist'];
    $variables['list_direction'] = $config->get('list_direction');
    $variables['searchField'] = $variables['location_search']['searchitem'];
    $map_data = [
      'itemlist' => $location_data['itemlist'],
      'marker' => $location_data['marker'],
      'get_direction' => $config->get('infowindow_direction'),
    ];
    $variables['#attached']['drupalSettings']['locator'] = $map_data;
    $variables['#attached']['library'][] = 'store_locator/store_locator.page';
    $googleMapKey = GoogleApiKeyHelper::getGoogleApiKey();
    $variables['#attached']['html_head'][] = [$googleMapKey, 'googleMapKey'];
  }
  else {
    $variables['empty_data'] = $config->get('message');
  }
}
