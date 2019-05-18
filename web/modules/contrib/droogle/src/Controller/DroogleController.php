<?php

/**
 * @file
 * Contains \Drupal\droogle\Controller\DroogleController.
 */

namespace Drupal\droogle\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\droogle\DroogleConnector;

/**
 * Returns responses for System routes.
 */
class DroogleController extends ControllerBase {

  /**
   * Callback for main droogle page.
   */
  public function droogleNavigator() {
    $connector = new DroogleConnector();
    $result = $connector->droogleOpenGDrive();

    return $result;
  }
}