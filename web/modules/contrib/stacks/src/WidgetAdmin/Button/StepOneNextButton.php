<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\stacks\WidgetAdmin\Step\StepsEnum;

/**
 * Class StepOneNextButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepOneNextButton extends BaseButton {

  /**
   * @inheritDoc.
   */
  public function getKey() {
    return 'next';
  }

  /**
   * @inheritDoc.
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Next'),
      '#goto_step' => StepsEnum::STEP_TWO,
      '#attributes' => [
        'class' => ['button--primary'],
      ],
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
