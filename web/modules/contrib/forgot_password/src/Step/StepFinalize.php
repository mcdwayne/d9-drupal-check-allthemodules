<?php
namespace Drupal\forgot_password\Step;

/**
 * Class StepFinalize.
 *
 * @package Drupal\forgot_password\Step
 */
class StepFinalize extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_FINALIZE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements() {

    $form['completed'] = [
      '#markup' => t('Your password has successfully been reset.'),
    ];

    return $form;
  }
}