<?php

namespace Drupal\Tests\cognito\Unit;

use Drupal\cognito\CognitoMessagesInterface;

/**
 * Stub message service with default messages, only for testing.
 */
class CognitoMessagesStub implements CognitoMessagesInterface {

  /**
   * {@inheritdoc}
   */
  public function accountBlocked() {
    return 'Your account is blocked';
  }

  /**
   * {@inheritdoc}
   */
  public function passwordResetRequired() {
    return 'Your account needs a password reset. Please <a href="/user/password">reset here</a> to login.';
  }

  /**
   * {@inheritdoc}
   */
  public function registrationComplete() {
    return 'Thank you for registering. A confirmation code was sent to the email address entered.';
  }

  /**
   * {@inheritdoc}
   */
  public function registrationConfirmed() {
    return 'Your account is now confirmed.';
  }

  /**
   * {@inheritdoc}
   */
  public function confirmationResent() {
    return 'You have already attempted to register but did not confirm your account. We have resent the email, please confirm here.';
  }

  /**
   * {@inheritdoc}
   */
  public function userAlreadyExistsRegister() {
    return 'You already have an account. Maybe try and <a href="/user/login">login</a>?';
  }

  /**
   * {@inheritdoc}
   */
  public function clickToConfirm() {
    return 'Please click the link in your email to confirm your account.';
  }

}
