<?php
/**
 * @file
 * og_sm_comment.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function og_sm_comment_views_default_views() {
  $files = file_scan_directory(
    drupal_get_path('module', 'og_sm_comment') . '/views', '/.view.inc/'
  );

  $views = array();
  foreach ($files as $filepath => $file) {
    require $filepath;
    if (isset($view)) {
      $views[$view->name] = $view;
    }
  }

  // Check that there are views in the directory.
  if ($views) {
    return $views;
  }
}
