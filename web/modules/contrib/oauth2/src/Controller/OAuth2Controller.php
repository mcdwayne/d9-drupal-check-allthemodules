<?php

namespace Drupal\oauth2\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * @file
 * Contains \Drupal\oauth2\Controller\OAuth2Controller.
 */

/**
 * Controller routines for OAuth 2.0.
 */
class OAuth2Controller extends ControllerBase {

  /**
   * Demo index controller.
   */
  public function index() {
    return [
      '#type' => 'markup',
      '#markup' => t('Hello World!!'),
    ];
  }

}
