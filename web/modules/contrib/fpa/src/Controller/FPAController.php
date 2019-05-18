<?php

/**
 * @file
 * Contains Drupal\fpa\Controller\FPAController.
 */

namespace Drupal\fpa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fpa\FPAFormBuilder;

/**
 * Class FPAController.
 *
 * @package Drupal\fpa\Controller
 */
class FPAController extends ControllerBase {

  public function permissionsList() {
    $render = FPAFormBuilder::buildFPAPage();

    return $render;
  }

}
