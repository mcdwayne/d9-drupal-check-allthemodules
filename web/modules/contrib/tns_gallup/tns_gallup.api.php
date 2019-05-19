<?php

/**
 * @file
 * API documentation for the TNS Gallup module.
 */

/**
 * Alter the TNS Gallup content path to be used on the page.
 *
 * @param string &$content_path
 *   The TNS Gallup content path. Initial value is the configured Site ID.
 */
function hook_tns_gallup_content_path_alter(&$content_path) {
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $content_path .= '/frontpage';
  }
}
