<?php

/**
 * @file
 * Post update functions.
 */

/**
 * Set new api url if needed.
 */
function riddle_marketplace_post_update_api_url() {
  $module_settings = Drupal::configFactory()->getEditable('riddle_marketplace.settings');
  $api_url = $module_settings->get('riddle_marketplace.api_url');
  $new_api_url = 'https://www.riddle.com/api/creators/riddle/get-list?token=%%TOKEN%%';

  if ($api_url != $new_api_url) {
    $module_settings->set('riddle_marketplace.api_url', $new_api_url);
    $module_settings->save();
  }
}
