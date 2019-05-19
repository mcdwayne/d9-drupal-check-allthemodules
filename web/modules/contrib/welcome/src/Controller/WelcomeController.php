<?php

namespace Drupal\welcome\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class WelcomeController.
 *
 * @package Drupal\welcome\Controller
 */
class WelcomeController extends ControllerBase {
  /**
   * Welcome.
   *
   * @return string
   *   Return Hello string.
   */
  public function welcome() {
    return [
      '#markup' => $this->t('Welcome to my site')
    ];
  }

}
