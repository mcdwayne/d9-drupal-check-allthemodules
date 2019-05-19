<?php

/**
 * @file
 * Hook implementations for hooks provided by Views API.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_post_execute().
 */
function views_inject_views_post_execute(ViewExecutable $view) {
  $extenders = $view->getDisplay()->getExtenders();
  if (empty($extenders['inject_results']) || !$extenders['inject_results']->shouldInject()) {
    return;
  }
  $extenders['inject_results']->postExecute();
}
