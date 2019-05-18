<?php
/**
 * @file
 * Google Analytics Vimeo Video Tracking inc.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Based on visibility setting this function returns TRUE/FALSE.
 */
function google_analytics_vimeo_visibility_pages() {
  static $page_match;
  $config = \Drupal::config('google_analytics_vimeo.admin_settings');
  $account = \Drupal::currentUser();
  $php_access = $account->hasPermission('use PHP for vimeo tracking visibility');

  // Cache visibility result if function is called more than once.
  if (!isset($page_match)) {

    $visibility_mode = $config->get('visibility.visibility_mode');
    $pages = $config->get('visibility.request_path_pages');

    // Match path if necessary.
    if (!empty($pages)) {
      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = Unicode::strtolower($pages);
      if ($visibility_mode < 2) {
        // Compare the lowercase path alias (if any) and internal path.
        $path = \Drupal::service('path.current')->getPath();
        $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')->getAliasByPath($path));
        $page_match = \Drupal::service('path.matcher')->matchPath($path_alias, $pages) ||
        (($path != $path_alias) && \Drupal::service('path.matcher')->matchPath($path, $pages));
        // When $visibility_mode has a value of 0, the tracking code is
        // displayed on all pages except those listed in $pages. When
        // set to 1, it is displayed only on those pages listed in $pages.
        $page_match = !($visibility_mode xor $page_match);
      }
      elseif (\Drupal::moduleHandler()->moduleExists('php') && $php_access) {
        $page_match = php_eval($pages);
      }
      else {
        $page_match = FALSE;
      }
    }
    else {
      $page_match = TRUE;
    }

  }
  return $page_match;
}
