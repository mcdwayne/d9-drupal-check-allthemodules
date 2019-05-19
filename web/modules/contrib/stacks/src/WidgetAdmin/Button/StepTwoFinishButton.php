<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\stacks\WidgetAdmin\Step\StepsEnum;

/**
 * Class StepTwoFinishButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepTwoFinishButton extends BaseButton {

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
      '#value' => t('Save Widget'),
      '#goto_step' => StepsEnum::STEP_FINALIZE,
      '#submit_handler' => 'submitValues',
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
