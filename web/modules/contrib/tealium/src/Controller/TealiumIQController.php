<?php

namespace Drupal\tealium\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controller class for menu redirect.
 */
class TealiumIQController extends ControllerBase {

  /**
   * Handles TeliumIQ redirect.
   */
  public function handleRedirect() {
    return new TrustedRedirectResponse('https://my.tealiumiq.com');
  }

}
