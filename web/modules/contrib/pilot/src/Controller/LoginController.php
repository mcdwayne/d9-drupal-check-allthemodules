<?php

namespace Drupal\pilot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Pilot Controller
 */
class LoginController extends ControllerBase {

  /**
   * Take token, check with server and login user.
   */
  public function login(Request $request) {
    $token = $request->get('token');

    // We access our configuration.
    $pilot_config = \Drupal::configFactory()->getEditable('pilot.settings');

    if (empty($pilot_config->get('pilot.token'))) {
      return;
    }

    if (empty($token)) {
      $response = new RedirectResponse('/user');
      $response->send();
      return;
    }

    try {
      $response = \Drupal::httpClient()
        ->get('https://drupalpilot.com/api/token?api_token=' . $pilot_config->get('pilot.token') . '&token=' . $token);
    } catch (\Exception $e) {
      $response = new RedirectResponse('/user');
      $response->send();
      return;
    }

    // Login as user 0
    $user = \Drupal::service('entity_type.manager')->getStorage('user')->load(0);

    // Login
    user_login_finalize($user);

    // Login separate pilot message
    \Drupal::logger('pilot')->notice('Session opened for %name.', array('%name' => $user->getUsername()));

    $response = new RedirectResponse('/admin');
    $response->send();
    return;
  }

}
