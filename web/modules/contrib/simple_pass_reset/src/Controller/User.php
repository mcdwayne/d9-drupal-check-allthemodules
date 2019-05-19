<?php

namespace Drupal\simple_pass_reset\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

/**
 * Alter User Reset password page. 
 */
class User extends ControllerBase {

  /**
   * Displays user password reset form.
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $account = \Drupal::currentUser();

    // The current user is already logged in.
    if ($account->isAuthenticated() && $account->id() == $uid) {
      user_logout();
      // We need to begin the redirect process again because logging out will
      // destroy the session.
      return $this->redirect('user.reset', [
        'uid' => $uid,
        'timestamp' => $timestamp,
        'hash' => $hash,
      ]);
    }
    $formObject = \Drupal::entityManager()
      ->getFormObject('user', 'default')
      ->setEntity($user);
    return $this->formBuilder()->getForm($formObject);
  }

}
