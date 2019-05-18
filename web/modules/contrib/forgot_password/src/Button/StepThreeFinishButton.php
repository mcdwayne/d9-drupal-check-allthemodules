<?php
namespace Drupal\forgot_password\Button;

use Drupal\forgot_password\Step\StepsEnum;

/**
 * Class StepThreeFinishButton.
 *
 * @package Drupal\forgot_password\Button
 */
class StepThreeFinishButton extends BaseButton {

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'finish';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Finish!'),
      '#goto_step' => StepsEnum::STEP_FINALIZE,
      '#submit_handler' => 'submitValues',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandler() {
    return 'submitIntake';
  }
}