<?php

namespace Drupal\stacks\WidgetAdmin\Step;

/**
 * Class StepFinalize.
 * @package Drupal\stacks\WidgetAdminStep
 */
class StepFinalize extends BaseStep {

  /**
   * @inheritDoc.
   */
  public function setStep() {
    return StepsEnum::STEP_FINALIZE;
  }

  /**
   * @inheritDoc.
   */
  public function getButtons() {
    return [];
  }

  /**
   * @inheritDoc.
   */
  public function buildStepFormElements() {

    // Get all the info from step #1.
    $step1 = $this->getStepValues(1);
    $delta = (int) $step1['delta'];
    $widget_instance_id = (int) $step1['widget_instance_id'];

    // Add JS for the finalized step.
    $form['#attached']['library'][] = 'stacks/admin_widget_finalize_forms';
    $form['#attached']['drupalSettings']['stacks']['finalize']['delta'] = $delta;
    $form['#attached']['drupalSettings']['stacks']['finalize']['widget_instance_id'] = $widget_instance_id;

    $form['completed'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => t('Loading...'),
      '#attributes' => [
        'id' => 'completed_message',
      ],
    ];

    return $form;
  }
}
