<?php

/**
 * @file
 * Form alter callback from tfl module hook.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\tfl\Controller\AccountChecker;
use Drupal\tfl\Controller\Login2Factor;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * {@inheritdoc}
 */
function tfl_user_login_form_alter(array & $form, FormStateInterface $form_state) {
  $config = \Drupal::config('tfl.settings');

  $form_state->setCached(FALSE);

  if (!empty($config->get('apikey')) && $config->get('status')) {
    $form['actions']['submit']['#value'] = 'Next';
    $form['#validate'][] = '_tfl_login_form_validation';
    $form['actions']['submit']['#submit'][] = '_tfl_login_form_submit';
  }
}

/**
 * Validation callback login form alter.
 */
function _tfl_login_form_validation(&$form, FormStateInterface $form_state) {
  $accountChecker = new AccountChecker();
  $account = $accountChecker->authenticate($form_state->getValue('name'), $form_state->getValue('pass'));

  if ($account && empty($account->field_mobile_number->value)) {
    $form_state->setError($form, 'Your mobile number is empty. Please contact administrator.');
  }
}

/**
 * Submit callback login form alter.
 */
function _tfl_login_form_submit(&$form, FormStateInterface $form_state) {
  $accountChecker = new AccountChecker();
  $login2Factor = new Login2Factor();
  $account = $accountChecker->authenticate($form_state->getValue('name'), $form_state->getValue('pass'));
  
  if (!is_null($account->id()) && $account->id() > 0) {
    $sendOtp = $login2Factor->sendOtp($account->getUsername());
    if ($sendOtp && !is_null($sendOtp->getStatusCode()) && $sendOtp->getStatusCode() == 200) {
      $otp_data = json_decode($sendOtp->getContent());
      $otp_session_id = $accountChecker->encryptOrDecrypt('encrypt', $otp_data->Details);
      $response = new RedirectResponse('/user/login/otp/' . $account->id() . '/' . $otp_session_id);
      $response->send();
      return;
    }
  }
}
