<?php

/**
 * @file
 * Post update hook implementations for the Akamai Drupal 8 integration module.
 */

/**
 * Issue #3011797: Remove devel mode config.
 */
function akamai_post_update_3011797_remove_devel_mode() {
  $config = \Drupal::service('config.factory')->getEditable('akamai.settings');
  $settings = $config->get();
  if (isset($settings['devel_mode'])) {
    $config->clear('devel_mode');
  }
  if (isset($settings['mock_endpoint'])) {
    $config->clear('mock_endpoint');
  }
  $config->save();
}
