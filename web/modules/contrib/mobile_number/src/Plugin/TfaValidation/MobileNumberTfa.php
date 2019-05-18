<?php

/**
 * @file
 * MobileNumberTfa.php
 */

use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\tfa\Plugin\TfaSendInterface;
use Drupal\user\UserDataInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\mobile_number\Exception\MobileNumberException;

/**
 * Class MobileNumberTfa is a validation and sending plugin for TFA.
 *
 * @package Drupal\mobile_number
 *
 * @ingroup mobile_number
 */
class MobileNumberTfa extends TfaBasePlugin implements TfaValidationInterface, TfaSendInterface {
  /**
   * Libphonenumber Utility object.
   *
   * @var \Drupal\mobile_number\MobileNumberUtilInterface
   */
  public $mobileNumberUtil;

  /**
   * Libphonenumber phone number object.
   *
   * @var \libphonenumber\PhoneNumber
   */
  public $mobileNumber;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);
    $this->mobileNumberUtil = \Drupal::service('mobile_number.util');

    if (!empty($context['validate_context']) && !empty($context['validate_context']['code'])) {
      $this->code = $context['validate_context']['code'];
    }

    if (!empty($context['validate_context']) && !empty($context['validate_context']['verification_token'])) {
      $this->verificationToken = $context['validate_context']['verification_token'];
    }

    $this->codeLength = 4;

    if ($m = $this->mobileNumberUtil->tfaAccountNumber($context['uid'])) {
      try {
        $this->mobileNumber = $this->mobileNumberUtil->testMobileNumber($m);
      }
      catch (MobileNumberException $e) {
        throw new Exception("Two factor authentication failed: \n" . $e->getMessage(), $e->getCode());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return $this->mobileNumberUtil->tfaAccountNumber(($this->context['uid'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function begin() {
    if (!$this->code) {
      if (!$this->sendCode()) {
        drupal_set_message(t('Unable to deliver the code. Please contact support.'), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $local_number = $this->mobileNumberUtil->getLocalNumber($this->mobileNumber);
    $numberClue = str_pad(substr($local_number, -3, 3), strlen($local_number), 'X', STR_PAD_LEFT);
    $numberClue = substr_replace($numberClue, '-', 3, 0);

    $form['code'] = ['#type' => 'textfield', '#title' => t('Verification Code'), '#required' => TRUE, '#description' => t('A verification code was sent to %clue. Enter the @length-character code sent to your device.', ['@length' => $this->codeLength, '%clue' => $numberClue])];

    $form['actions']['#type'] = 'actions';
    $form['actions']['login'] = ['#type' => 'submit', '#value' => t('Verify')];
    $form['actions']['resend'] = ['#type' => 'submit', '#value' => t('Resend'), '#submit' => ['tfa_form_submit'], '#limit_validation_errors' => []];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    // If operation is resend then do not attempt to validate code.
    if ($form_state['values']['op'] === $form_state['values']['resend']) {
      return TRUE;
    }
    elseif (!$this->verifyCode($form_state['values']['code'])) {
      $this->errorMessages['code'] = t('Invalid code.');
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface &$form_state) {
    // Resend code if pushed.
    if ($form_state['values']['op'] === $form_state['values']['resend']) {
      if (!$this->mobileNumberUtil->checkFlood($this->mobileNumber, 'sms')) {
        drupal_set_message(t('Too many verification code requests, please try again shortly.'), 'error');
      }
      elseif (!$this->sendCode()) {
        drupal_set_message(t('Unable to deliver the code. Please contact support.'), 'error');
      }
      else {
        drupal_set_message(t('Code resent'));
      }

      return FALSE;
    }
    else {
      return parent::submitForm($form, $form_state);
    }
  }

  /**
   * Return context for this plugin.
   */
  public function getPluginContext() {
    return ['code' => $this->code, 'verification_token' => !empty($this->verificationToken) ? $this->verificationToken : ''];
  }

  /**
   * Send the code via the client.
   *
   * @return bool
   *   Where sending sms was successful.
   */
  public function sendCode() {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($this->context['uid']);
    $this->code = $this->mobileNumberUtil->generateVerificationCode($this->codeLength);
    try {
      $message = \Drupal::configFactory()->getEditable('mobile_number.settings')->get('tfa_message');
      $message = $message ? $message : $this->mobileNumberUtil->MOBILE_NUMBER_DEFAULT_SMS_MESSAGE;
      if (!($this->verificationToken = $this->mobileNumberUtil->sendVerification($this->mobileNumber, $message, $this->code, ['user' => $user]))) {
        return FALSE;
      }

      // @todo Consider storing date_sent or date_updated to inform user.
      \Drupal::logger('mobile_number_tfa')->info('TFA validation code sent to user @uid', ['@uid' => $this->context['uid']]);
      return TRUE;
    }
    catch (Exception $e) {
      \Drupal::logger('mobile_number_tfa')->error('Send message error to user @uid. Status code: @code, message: @message', ['@uid' => $this->context['uid'], '@code' => $e->getCode(), '@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Verifies the given code with this session's verification token.
   *
   * @param string $code
   *   Code.
   *
   * @return bool
   *   Verification status.
   */
  public function verifyCode($code) {
    return $this->isValid = $this->mobileNumberUtil->verifyCode($this->mobileNumber, $code, $this->verificationToken);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbacks() {
    return ($this->pluginDefinition['fallbacks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function purge() {}

}
