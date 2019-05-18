<?php

namespace Drupal\kashing\Entity;

use Drupal\user\Entity\User;

/**
 * Kashing Field Validation class.
 */
class KashingValid {

  /**
   * Validate required field.
   */
  public function validateRequiredField($field_value) {

    if (isset($field_value) && $field_value != '') {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Validate Amount field.
   */
  public function validateAmountField($field_value) {

    if (filter_var($field_value, FILTER_VALIDATE_FLOAT) && $field_value > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Validate ID field.
   */
  public function validateIdField($field_value) {

    if (isset($field_value) && preg_match('/^[a-z0-9_]+$/i', $field_value)) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Validate API keys.
   */
  public function validateApiKeys() {

    $config = \Drupal::service('config.factory')->getEditable('kashing.settings');

    $mode = $config->get('mode');

    $error_info = '';

    if ($mode == 'test') {
      $merchant_id = $config->get('key.test.merchant');
      if (!$this->validateRequiredField($merchant_id)) {
        $error_info .= '<li>' . t('No test merchant ID provided.') . '</li>';
      }

      $secret_key = $config->get('key.test.secret');
      if (!$this->validateRequiredField($secret_key)) {
        $error_info .= '<li>' . t('No test secret key provided.') . '</li>';
      }
    }
    elseif ($mode == 'live') {
      $merchant_id = $config->get('key.live.merchant');
      if (!$this->validateRequiredField($merchant_id)) {
        $error_info .= '<li>' . t('No live merchant ID provided.') . '</li>';
      }

      $secret_key = $config->get('key.live.secret');
      if (!$this->validateRequiredField($secret_key)) {
        $error_info .= '<li>' . t('No live secret key provided.') . '</li>';
      }
    }
    else {
      $error_info .= '<li>' . t('No Kashing mode selected.') . '</li>';
    }

    if ($error_info != '') {
      return $error_info;
    }

    return TRUE;

  }

  /**
   * Check if user is an admin.
   */
  public function isAdmin() {
    $user_id = \Drupal::currentUser()->id();
    $is_admin = User::load($user_id)->hasRole('administrator');
    return $is_admin;
  }

}
