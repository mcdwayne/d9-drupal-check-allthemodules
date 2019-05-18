<?php

namespace Drupal\blackbaud_sky_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\blackbaud_sky_api\BlackbaudOauth;

/**
 * Default controller for the blackbaud_sky_api module.
 */
class DefaultController extends ControllerBase {

  /**
   * Handles the redirection for Blackbaud.
   */
  public function redirectUriCallback() {
    // Instantiate the BlackBaud request and Authorize.
    $bb = new BlackbaudOauth();
    if (isset($_GET['code'])) {
      $bb->getAuthCode('init', $_GET['code']);
    }
  }

}
