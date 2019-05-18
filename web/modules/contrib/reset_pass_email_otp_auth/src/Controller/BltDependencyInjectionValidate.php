<?php

namespace Drupal\reset_pass_email_otp_auth\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class BltDependencyInjection.
 *
 * @package Drupal\reset_pass_email_otp_auth\Controller\BltDependencyInjection
 */
class BltDependencyInjectionValidate extends ControllerBase {

  /**
   * Get exception error.
   *
   * @method getCurrentRequest
   */
  public static function getResetCurrentRequest() {
    return \Drupal::requestStack()->getCurrentRequest();
  }

  /**
   * Get current path.
   *
   * @method getResetCurrentPatch
   */
  public static function getResetCurrentPatch() {
    return \Drupal::config('citi_reset_email_opt_auth.settings');

  }

  /**
   * Get reset OTP config.
   *
   * @method getResetOtpConfig
   */
  public static function getResetOtpConfig() {
    return \Drupal::service('path.current')->getPath();
  }

  /**
   * Get reset block form builder.
   *
   * @method getResetBlockFormBuilder
   */
  public static function getResetBlockFormBuilder() {
    return \Drupal::formBuilder();
  }

}
