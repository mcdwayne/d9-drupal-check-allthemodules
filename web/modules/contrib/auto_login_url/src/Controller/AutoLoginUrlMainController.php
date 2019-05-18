<?php

namespace Drupal\auto_login_url\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AutoLoginUrlMainController extends ControllerBase {

  /**
   * Auto login method.
   *
   * @param string $hash
   *   The hash string on the URL.
   */
  public function login($uid, $hash) {

    // Disable page cache.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Check for flood events.
    if (\Drupal::service('auto_login_url.general')->checkFlood()) {
      drupal_set_message($this->t('Sorry, too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later.'), 'error');

      throw new AccessDeniedHttpException();
    }

    $destination = \Drupal::service('auto_login_url.login')->login($uid, $hash);

    if ($destination) {
      return new RedirectResponse($destination);
    }
    else {
      // Register flood event.
      \Drupal::service('auto_login_url.general')->registerFlood($hash);

      throw new AccessDeniedHttpException();
    }
  }
}
