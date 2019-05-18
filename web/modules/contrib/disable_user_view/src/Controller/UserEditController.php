<?php

namespace Drupal\disable_user_view\Controller;

use Drupal\user\Controller\UserController;

/**
 * Controller routines for user routes.
 */
class UserEditController extends UserController {
  /**
   * Redirects users to their profile page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {
    return $this->redirect('entity.user.edit_form', ['user' => $this->currentUser()->id()]);
  }
}
