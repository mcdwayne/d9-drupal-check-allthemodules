<?php

/**
 * @file
 * Contains \Drupal\password_haveibeenpwned\Controller\PasswordHaveIBeenPwnedController.
 */

namespace Drupal\password_haveibeenpwned\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for password_haveibeenpwned routes.
 */
class PasswordHaveIBeenPwnedController extends ControllerBase {

  /**
   * Redirect an authenticated user to their user edit page.
   */
  public function redirectEdit() {
    // todo: explicity ensure this redirect response is not cacheable.
    return new RedirectResponse(Url::fromRoute('entity.user.edit_form', ['user' => \Drupal::currentUser()->id()])->toString());
  }

}
