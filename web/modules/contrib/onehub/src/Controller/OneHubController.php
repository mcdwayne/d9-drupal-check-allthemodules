<?php

namespace Drupal\onehub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\onehub\OneHubOauth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the onehub module.
 */
class OneHubController extends ControllerBase {

  /**
   * Redirect URI callback to grab the code token.
   *
   * @return null
   *   See Onehub->getAccessCode() for return path.
   */
  public function redirectUriCallback() {
    // Instantiate the OneHub request and Authorize.
    $oh = new OneHubOauth();
    if (isset($_GET['code'])) {
      $oh->getAccessCode($_GET['code']);
    }

    return new Response('', 200);
  }

}
