<?php

namespace Drupal\shib_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class LogoutController.
 *
 * @package Drupal\shib_auth\Controller
 */
class LogoutController extends ControllerBase {

  /**
   * Logout-- kills drupal then Redirects to shibboleth logout page.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function logout() {

    // Logs the current user out of drupal.
    user_logout();

    // Get shibboleth config settings.
    $config = \Drupal::config('shib_auth.shibbolethsettings');
    // Get shibboleth advanced config settings.
    $adv_config = \Drupal::config('shib_auth.advancedsettings');

    // The shibboleth logout URL to redirect to.
    $logout_url = $config->get('shibboleth_logout_handler_url');

    // Append the return url if it is set in the admin.
    if ($adv_config->get('url_redirect_logout')) {
      $logout_url .= '?return=' . $adv_config->get('url_redirect_logout');
    }

    // Redirect to the shib logout page.
    return new TrustedRedirectResponse($logout_url);
  }

  /**
   * Logout error.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function logoutError() {

    // Logs the current user out of drupal.
    user_logout();

    // Get shibboleth config settings.
    $config = \Drupal::config('shib_auth.shibbolethsettings');

    // The shibboleth logout URL to redirect to with drupal error appended.
    $logout_url = $config->get('shibboleth_logout_handler_url') . '?return=' . Url::fromRoute('shib_auth.logout_controller_logout_error_page')
      ->toString();

    // Redirect to the shibboleth logout page.
    return new TrustedRedirectResponse($logout_url);

  }

  /**
   * Error page for logout.
   *
   * @return array
   *   A renderable array.
   */
  public function logoutErrorPage() {

    // Get shibboleth advanced config settings.
    $adv_config = \Drupal::config('shib_auth.advancedsettings');

    return [
      '#type' => 'markup',
      '#markup' => $adv_config->get('logout_error_message'),
    ];
  }

}
