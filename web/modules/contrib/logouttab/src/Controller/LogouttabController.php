<?php

/**
 * @file
 * Contains \Drupal\logouttab\Controller\LogouttabController.
 */

namespace Drupal\logouttab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements redirect from logout tab.
 */
class LogouttabController extends ControllerBase {

  /**
   * Redirects user to configured logout page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to configured page.
   */
  public function logout() {
    $url = Url::fromUserInput('/' . $this->config('logouttab.settings')->get('url'));
    return new RedirectResponse($url->setAbsolute()->toString());
  }

}
