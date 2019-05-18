<?php

namespace Drupal\otl_logout;

use Drupal\user\Controller\UserController;

/**
 * Main controller for the OTL Logout module.
 */
class OtlLogoutController extends UserController {

  /**
   * {@inheritdoc}
   */
  public function resetPassLogin($uid, $timestamp, $hash) {
    // If the current user is logged in then log out the user and reload the
    // current path. On the next go-around the visitor will be logged out and
    // will be able to complete the password reset process.
    $account = $this->currentUser();
    if ($account->isAuthenticated()) {
      user_logout();
      return $this->redirect(
        'user.reset.login',
        [
          'uid' => $uid,
          'timestamp' => $timestamp,
          'hash' => $hash,
        ]
      );
    }

    // If the visitor is not logged in then proceed as normal.
    else {
      return parent::resetPassLogin($uid, $timestamp, $hash);
    }
  }

}
