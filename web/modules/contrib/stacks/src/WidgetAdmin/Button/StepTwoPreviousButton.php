<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\stacks\WidgetAdmin\Step\StepsEnum;

/**
 * Class StepTwoPreviousButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepTwoPreviousButton extends BaseButton {

  /**
   * @inheritDoc.
   */
  public function getKey() {
    return 'previous';
  }

  /**
   * @inheritDoc.
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#goto_step' => StepsEnum::STEP_ONE,
      '#skip_validation' => TRUE,
      '#previous' => TRUE,
      '#attributes' => [
        'class' => ['link--gray'],
      ],
    ];
  }

}
