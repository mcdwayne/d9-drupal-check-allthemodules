<?php

namespace Drupal\cognito_tests;

use Drupal\cognito\Aws\CognitoBase;
use Drupal\cognito\Aws\CognitoResult;

/**
 * Implementation for testing.
 */
class NullCognito extends CognitoBase {

  /**
   * {@inheritdoc}
   */
  public function authorize($username, $password) {
    return $this->wrap(function () use ($password) {
      return $password === 'letmein' ? new CognitoResult([
        'AuthenticationResult' => [
          'AccessToken' => '123',
          'IdToken' => '123',
        ],
      ]) : new CognitoResult([], new \Exception('Failed to login'));
    });
  }

  /**
   * {@inheritdoc}
   */
  public function signUp($username, $password, $email, array $userAttributes = []) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function resendConfirmationCode($username) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function confirmSignup($username, $confirmCode) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function forgotPassword($username) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForgotPassword($username, $password, $confirmationCode) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function getUser($accessToken) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserAttributes($accessToken, array $userAttributes) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminEnableUser($username) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminDisableUser($username) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminSignup($username, $email, $messageAction = '', array $userAttributes = []) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function changePassword($accessToken, $oldPassword, $newPassword) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminRespondToNewPasswordChallenge($username, $challengeType, $challengeAnswer, $session) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminInitiateAuth($username, $password) {
    return $this->wrap(function () {

    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminUpdateUserAttributes($username, $attributeName, $attributeValue) {
    return $this->wrap(function () {

    });
  }

}
