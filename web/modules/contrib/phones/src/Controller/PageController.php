<?php

namespace Drupal\phones\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class PageController extends ControllerBase {

  /**
   * Page.
   */
  public function page() {
    $output = "Phones";
    return [
      'output' => ['#markup' => $output],
    ];
  }

  /**
   * Page.
   */
  public function dahsboard() {
    $output = "Dahsboard";
    return [
      'output' => ['#markup' => $output],
    ];
  }

}
