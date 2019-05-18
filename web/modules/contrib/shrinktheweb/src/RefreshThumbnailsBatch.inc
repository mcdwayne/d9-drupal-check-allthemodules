<?php

/**
 * Request refresh batch process callback.
 */
function requestThumbnailsRefresh($url, &$context) {
  $context['results'][] = $url;
  module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');
  shrinktheweb_refreshThumbnail($url, $aOptions = array());
  $context['message'] = 'Refresh for thumbnail for domain <em>' . $url . '</em> was requested';
}

/**
 * Request refresh batch finish callback.
 */
function requestThumbnailsRefreshFinished($success, $results, $operations) {
  if ($success) {
    drupal_set_message('Thumbnails for ' . count($results) . ' domains requested to be refreshed');
  }
  else {
    drupal_set_message('An error has occurred.', 'error');
  }
}
