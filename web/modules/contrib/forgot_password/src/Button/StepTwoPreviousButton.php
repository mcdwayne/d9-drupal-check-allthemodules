<?php
namespace Drupal\forgot_password\Button;

use Drupal\forgot_password\Step\StepsEnum;

/**
 * Class StepTwoPreviousButton.
 *
 * @package Drupal\forgot_password\Button
 */
class StepTwoPreviousButton extends BaseButton {

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
      '#goto_step' => StepsEnum::STEP_ONE,
      '#skip_validation' => TRUE,
    ];
  }
}