<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\stacks\WidgetAdmin\Step\StepsEnum;

/**
 * Class StepTwoFinishButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepExistingFinishButton extends BaseButton {

  /**
   * @inheritDoc.
   */
  public function getKey() {
    return 'finishexisting';
  }

  /**
   * @inheritDoc.
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Add Existing Widget'),
      '#goto_step' => StepsEnum::STEP_FINALIZE,
      '#skip_validation' => TRUE,
      '#attributes' => [
        'style' => 'display: none',
        'class' => ['button--primary'],
      ],
    ];
  }

  /**
   * @inheritDoc.
   */
  public function getSubmitHandler() {
    return 'submitValuesExisting';
  }

}
