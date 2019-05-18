<?php
namespace Drupal\forgot_password\Button;

use Drupal\forgot_password\Step\StepsEnum;

/**
 * Class StepThreePreviousButton.
 *
 * @package Drupal\forgot_password\Button
 */
class StepThreePreviousButton extends BaseButton {
  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'previous';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#goto_step' => StepsEnum::STEP_TWO,
      '#skip_validation' => TRUE,
    ];
  }
}