<?php

/**
 * @file
 * Contains Drupal\devel_contrib\Controller\ViewsDataController.
 */

namespace Drupal\devel_contrib\Controller;

use Drupal\views\Views;

/**
 * Provides debug output of Views table and field data.
 */
class ViewsDataController {

  /**
   * Returns the page content.
   */
  function content() {
    $build = [];

    $views_data = Views::viewsData();
    $views_info = $views_data->get();
    ksort($views_info);
    dpm($views_info);

    return $build;
  }

}
