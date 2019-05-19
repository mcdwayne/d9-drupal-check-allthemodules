<?php

namespace Drupal\webserver_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Returns responses for team page routes.
 */
class WebserverAuthUserControllers extends ControllerBase {

  /**
   * Login page callback.
   */
  public function userLogin() {
    $config = \Drupal::config('webserver_auth.settings');

    if ($url = $config->get('login_url')) {
      $response = new TrustedRedirectResponse($url);
    }
    else {
      $response = new TrustedRedirectResponse('/');
    }

    return $response;
  }

  /**
   * Logout page callback.
   */
  public function userLogout() {
    $config = \Drupal::config('webserver_auth.settings');

    if ($url = $config->get('logout_url')) {
      $response = new TrustedRedirectResponse($url);
    }
    else {
      $response = new TrustedRedirectResponse('/');
    }

    return $response;
  }

  /**
   * Register page callback.
   */
  public function userRegister() {
    $config = \Drupal::config('webserver_auth.settings');

    if ($url = $config->get('register_url')) {
      $response = new TrustedRedirectResponse($url);
    }
    else {
      $response = new TrustedRedirectResponse('/');
    }

    return $response;
  }
}
