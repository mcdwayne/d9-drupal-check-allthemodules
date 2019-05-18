<?php

namespace Drupal\reset_pass_email_otp_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides Custom Block.
 *
 * @Block(
 * id = "reset_form_otp",
 * admin_label = @Translation("OTP Form Block"),
 * category = @Translation("Blocks")
 * )
 */
class OTPFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['form'] = \Drupal::formBuilder()
      ->getForm('Drupal\reset_pass_email_otp_auth\Form\OTPCheck');

    return $build;
  }

  /**
   * Implement max cache zero.
   *
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
