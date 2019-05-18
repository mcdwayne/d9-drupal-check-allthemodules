<?php

namespace Drupal\cognito;

/**
 * The cognito messages service.
 */
interface CognitoMessagesInterface {

  /**
   * Gets the account blocked message.
   *
   * @return string
   *   The message.
   */
  public function accountBlocked();

  /**
   * Gets the password reset required message.
   *
   * @return string
   *   The message.
   */
  public function passwordResetRequired();

  /**
   * Gets the registration complete message.
   *
   * @return string
   *   The message.
   */
  public function registrationComplete();

  /**
   * Gets the registration confirmed message.
   *
   * @return string
   *   The message.
   */
  public function registrationConfirmed();

  /**
   * Gets the confirmation resent message.
   *
   * @return string
   *   The message.
   */
  public function confirmationResent();

  /**
   * Gets the user already exists message during registration.
   *
   * @return string
   *   The message.
   */
  public function userAlreadyExistsRegister();

  /**
   * Click to confirm account.
   *
   * @return string
   *   The message.
   */
  public function clickToConfirm();

}
