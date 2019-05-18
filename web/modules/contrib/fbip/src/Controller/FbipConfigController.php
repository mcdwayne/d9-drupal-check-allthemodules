<?php

/**
 * @file
 * Contains \Drupal\fbip\Controller\FbipConfigController.
 */

namespace Drupal\fbip\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class FbipConfigController.
 *
 * @package Drupal\fbip\Controller
 */
class FbipConfigController extends ControllerBase {
  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index() {
    return [
        '#type' => 'markup',
        '#markup' => $this->t('Implement method: index')
    ];
  }

}
