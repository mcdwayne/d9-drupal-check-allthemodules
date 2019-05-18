<?php

namespace Drupal\otp_sms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sms\Exception\PhoneNumberSettingsException;
use Drupal\user\Entity\User;

/**
 * Enforce the one time password during login.
 */
class OtpSmsUserLoginFormAlter {

  use StringTranslationTrait;

  /**
   * The OTP SMS Service.
   *
   * @return \Drupal\otp_sms\OtpSmsProviderInterface
   *   The OTP SMS service.
   */
  public static function otpSmsProvider() {
    return \Drupal::service('otp_sms.provider');
  }

  /**
   * The OTP SMS logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The OTP SMS logger.
   */
  public static function logger() {
    return \Drupal::service('logger.channel.otp_sms');
  }

  /**
   * Implements hook_form_user_login_form_alter().
   */
  public function formAlter(&$form, FormStateInterface $form_state) {
    $original_validators = $form['#validate'];
    $form['#validate'] = [];
    foreach ($original_validators as $callable) {
      // Put in our validator before otp.
      if (isset($callable[0]) && strpos($callable[0], 'one_time_password') !== FALSE) {
        $form['#validate'][] = [static::class, 'validateOneTimePassword'];
      }
      $form['#validate'][] = $callable;
    }
  }

  /**
   * Determine if a user has any verified phone numbers.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   A user account.
   *
   * @return bool
   *   Whether the user has any verified phone numbers.
   */
  public static function userHasVerifiedPhoneNumbers(AccountInterface $user) {
    /** @var \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number */
    $phone_number = \Drupal::service('sms.phone_number');
    try {
      $phone_numbers = $phone_number->getPhoneNumbers($user);
      return count($phone_numbers) > 0;
    }
    catch (PhoneNumberSettingsException $e) {
      static::logger()->warning('Cannot send user entities SMS because phone number configuration is missing or invalid.');
    }
    return FALSE;
  }

  /**
   * Validate callback to verify the one time password code.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateOneTimePassword(array &$form, FormStateInterface $form_state) {
    if (!$uid = $form_state->get('uid')) {
      return;
    }

    $user = User::load($uid);
    if ($user->one_time_password->isEmpty() || !static::userHasVerifiedPhoneNumbers($user)) {
      return;
    }

    $user_provided_code = $form_state->getValue('one_time_password');
    if (empty($user_provided_code)) {
      $code_was_sent = static::otpSmsProvider()->maybeSendNewCode($user);
      if ($code_was_sent) {
        drupal_set_message(t('We just sent you a SMS containing a one time login. This code will expire soon.'));
      }
      else {
        drupal_set_message(t('We already sent you an SMS a while ago.'));
      }

      // Disable OTP. We're top dog now.
      $form_state->unsetValue('one_time_password');
    }
  }

}
