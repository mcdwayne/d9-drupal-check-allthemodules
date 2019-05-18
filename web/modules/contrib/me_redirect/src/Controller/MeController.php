<?php

namespace Drupal\me_redirect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class MeController
 *
 * @package Drupal\me_redirect\Controller
 */
class MeController extends ControllerBase {

  /**
   * Controller method for handling /me/* path redirection.
   *
   * @param $user_path
   *   The user path being requested.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response pointing to the correct user path.
   */
  public function me($user_path) {
    $user_id = \Drupal::currentUser()->id();

    if (!empty($user_id)) {
      // Logged in, replace "me" in path and redirect.
      $uri = '/user/' . $user_id;

      if (!empty($user_path)) {
        $user_path = str_replace(':', '/', $user_path);

        $uri .= '/' . $user_path;
      }

      return new RedirectResponse($uri, 302);
    } else {
      // Not logged in, throw access denied.
      throw new AccessDeniedHttpException();
    }
  }

}
