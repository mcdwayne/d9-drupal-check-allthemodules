<?php

namespace Drupal\simple_pass_reset\AccessChecks;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\Crypt;

/**
 * A custom access check.
 */
class ResetPassAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access($uid, $timestamp, $hash, AccountInterface $account) {
    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    // Time out, in seconds, until login URL expires.
    $timeout = \Drupal::config('user.settings')->get('password_reset_timeout');

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      return AccessResult::forbidden();
    }

    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // A different user is already logged in on the computer.
      if ($account->id() != $uid) {
        drupal_set_message(t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
          [
            '%other_user' => $account->getUsername(),
            '%resetting_user' => $user->getUsername(),
            ':logout' => \Drupal::url('user.logout'),
          ]), 'warning');
        // Throw access denied page.
        return AccessResult::forbidden();
      }
    }

    if ($timestamp <= REQUEST_TIME && $user) {
      // Bypass the timeout check if the user has not logged in before.
      if ($user->getLastLoginTime() && REQUEST_TIME - $timestamp > $timeout) {
        drupal_set_message(t('You have tried to use a one-time login link that has expired. Please request a new one using the <a href=":link">link</a>.', [':link' => \Drupal::url('user.pass')]), 'error');
        return AccessResult::forbidden();
      }
      elseif (($timestamp >= $user->getLastLoginTime()) && ($timestamp <= REQUEST_TIME) && Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
        return AccessResult::Allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
