<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\stacks\WidgetAdmin\Step\StepsEnum;

/**
 * Class StepTwoFinishEditButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepTwoFinishEditButton extends BaseButton {

  /**
   * @inheritDoc.
   */
  public function getKey() {
    return 'finish';
  }

  /**
   * @inheritDoc.
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Update Widget'),
      '#goto_step' => StepsEnum::STEP_FINALIZE,
      '#submit_handler' => 'submitValues',
      '#attributes' => ['class' => ['button--primary']],
      '#states' => [
        'disabled' => [
          [
            ':input[name="widget_name"]' => array('filled' => FALSE),
            ':input[name="reusable"]' => array('checked' => TRUE),
          ],
        ],
      ],
    ];
  }

}
