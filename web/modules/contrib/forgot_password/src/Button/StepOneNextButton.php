<?php
namespace Drupal\forgot_password\Button;

use Drupal\forgot_password\Step\StepsEnum;

/**
 * Class StepOneNextButton.
 *
 * @package Drupal\forgot_password\Button
 */
class StepOneNextButton extends BaseButton {

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'next';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Next'),
      '#goto_step' => StepsEnum::STEP_TWO,
    ];
  }

}