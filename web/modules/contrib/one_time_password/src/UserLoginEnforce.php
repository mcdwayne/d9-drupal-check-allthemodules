<?php

namespace Drupal\one_time_password;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

/**
 * Enforce the one time password during login.
 */
class UserLoginEnforce {

  use StringTranslationTrait;

  /**
   * The flood ID for the per-user flood control.
   */
  const FLOOD_NAME_UID = 'one_time_password.uid';

  /**
   * The flood ID for the IP based flood control.
   */
  const FLOOD_NAME_IP = 'one_time_password.ip';

  /**
   * The window of time flood entries exist for.
   */
  const FLOOD_WINDOW = 3600;

  /**
   * The maximum number of OTP attempts you are allowed.
   */
  const FLOOD_THRESHOLD = 5;

  /**
   * Implements hook_form_user_login_form_alter().
   */
  public function formUserLoginFormAlter(&$form, FormStateInterface $form_state) {
    $form['one_time_password'] = [
      '#title' => $this->t('One Time Password'),
      '#description' => $this->t('If you have two factor authentication enabled, enter your one time password.'),
      '#type' => 'textfield',
      '#size' => 6,
      '#maxlength' => 6,
    ];
    $form['#validate'][] = [static::class, 'validateOneTimePassword'];
  }

  /**
   * Validate callback to verify the one time password code.
   */
  public static function validateOneTimePassword(&$form, FormStateInterface $form_state) {
    // A user ID means the user was able to authenticate. We only care about
    // validating the TFA code if the user was already authenticated by a
    // password. If the user has not authenticated by a password, the form
    // validating will stop login anyway.
    if (!$uid = $form_state->get('uid')) {
      return;
    }

    // If the one_time_password field is empty, don't validate the users one
    // time password code. They haven't opted into using one time passwords.
    $user = User::load($uid);
    if ($user->one_time_password->isEmpty()) {
      return;
    }

    /** @var \Drupal\Core\Flood\FloodInterface $flood */
    $flood = \Drupal::service('flood');
    $ip_address = \Drupal::requestStack()->getCurrentRequest()->getClientIp();

    // Check if the user has attempted too many OTP guesses from a single IP
    // address or for a specific user.
    $flood_is_allowed_uid = $flood->isAllowed(static::FLOOD_NAME_UID, static::FLOOD_THRESHOLD, static::FLOOD_WINDOW, $uid);
    $flood_is_allowed_ip = $flood->isAllowed(static::FLOOD_NAME_IP, static::FLOOD_THRESHOLD, static::FLOOD_WINDOW, $ip_address);
    if (!$flood_is_allowed_uid || !$flood_is_allowed_ip) {
      $form_state->setErrorByName('one_time_password', t('There have been too many incorrect one time passwords entered.'));
      // Don't attempt to validate the OTP if the flood control has kicked in.
      return;
    }

    // Get the one time password object from the user and verify it against the
    // one time password that was submitted. The flood table validation runs
    // before this validation, so this is not a bruteforce vector. We only see
    // this message if the user has successfully validated their password and
    // have not tripped a flood threshold.
    /** @var \OTPHP\TOTP $one_time_pass */
    $one_time_pass = $user->one_time_password->getOneTimePassword();
    $user_provided_code = $form_state->getValue('one_time_password');
    if (empty($user_provided_code) || !$one_time_pass->verify($user_provided_code, NULL, 10)) {
      $form_state->setErrorByName('one_time_password', t('The entered two factor authentication code is incorrect.'));

      // If the OTP has been entered incorrectly, add an entry to the flood
      // table. Note: this is NOT registered if the user has already exceeded
      // the flood thresholds. This means logged repeated attempts will not
      // ever exceed the flood threshold.
      $flood->register(static::FLOOD_NAME_UID, static::FLOOD_WINDOW, $uid);
      $flood->register(static::FLOOD_NAME_IP, static::FLOOD_WINDOW, $ip_address);
    }
  }

}
