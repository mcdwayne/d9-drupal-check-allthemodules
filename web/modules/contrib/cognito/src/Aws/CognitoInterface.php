<?php

namespace Drupal\cognito\Aws;

/**
 * A helper service to signup and authorise users against Cognito.
 */
interface CognitoInterface {

  /**
   * Authorises a user against Cognito.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function authorize($username, $password);

  /**
   * Signs a user up in the user pool.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   * @param string $email
   *   Their email address.
   * @param array $userAttributes
   *   The user attributes.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function signUp($username, $password, $email, array $userAttributes = []);

  /**
   * Resend their confirmation code.
   *
   * @param string $username
   *   The username.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function resendConfirmationCode($username);

  /**
   * Confirms as users registration.
   *
   * @param string $username
   *   The username.
   * @param string $confirmCode
   *   Their verification code.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function confirmSignup($username, $confirmCode);

  /**
   * Resets as users password.
   *
   * @param string $username
   *   The username.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function forgotPassword($username);

  /**
   * Confirms the password reset request and updates the users password.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   * @param string $confirmationCode
   *   Their verification code.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function confirmForgotPassword($username, $password, $confirmationCode);

  /**
   * Changes a users password.
   *
   * @param string $accessToken
   *   The access token from the initiate auth request.
   * @param string $oldPassword
   *   The old or temporary password.
   * @param string $newPassword
   *   The new password.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function changePassword($accessToken, $oldPassword, $newPassword);

  /**
   * Gets the user attributes and metadata for a user.
   *
   * @param string $accessToken
   *   The access token from the auth request.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function getUser($accessToken);

  /**
   * Updates user attributes.
   *
   * @param string $accessToken
   *   The access token from the auth request.
   * @param array $userAttributes
   *   The user attributes.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function updateUserAttributes($accessToken, array $userAttributes);

  /**
   * Enables a users account.
   *
   * @param string $username
   *   The username.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function adminEnableUser($username);

  /**
   * Disables a users account.
   *
   * @param string $username
   *   The username.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function adminDisableUser($username);

  /**
   * Admin registration.
   *
   * @param string $username
   *   The username.
   * @param string $email
   *   The email.
   * @param string $messageAction
   *   If left empty, a welcome email will be sent.
   *   RESEND - Will resend the welcome email. The user must already exist
   *   otherwise an exception will be thrown.
   *   SUPPRESS - Will suppress all emails.
   * @param array $userAttributes
   *   The user attributes.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function adminSignup($username, $email, $messageAction = '', array $userAttributes = []);

  /**
   * Respond to the new password challenge.
   *
   * @param string $username
   *   The username.
   * @param string $challengeType
   *   One of the Cognito challenge types.
   * @param string $challengeAnswer
   *   The challenge answer.
   * @param string $session
   *   The unique session Id from the previous auth request.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   *
   * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminrespondtoauthchallenge
   */
  public function adminRespondToNewPasswordChallenge($username, $challengeType, $challengeAnswer, $session);

  /**
   * Updates a users attribute.
   *
   * @param string $username
   *   The users username.
   * @param string $attributeName
   *   The attribute name.
   * @param string $attributeValue
   *   The attribute value.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  public function adminUpdateUserAttributes($username, $attributeName, $attributeValue);

}
