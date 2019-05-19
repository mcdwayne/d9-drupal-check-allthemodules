<?php

namespace Drupal\stacks\WidgetAdmin\Manager;

use Drupal\stacks\WidgetAdmin\Step\StepInterface;
use Drupal\stacks\WidgetAdmin\Step\StepsEnum;
use Drupal\stacks\Entity\WidgetInstanceEntity;

/**
 * Class StepManager.
 * @package Drupal\stacks\WidgetAdmin\Manager
 */
class StepManager {

  protected $steps;

  /**
   * StepManager constructor.
   */
  public function __construct() {
  }

  /**
   * Add a step to the steps property.
   * @param \Drupal\stacks\WidgetAdmin\Step\StepInterface $step
   */
  public function addStep(StepInterface $step) {
    $this->steps[$step->getStep()] = $step;
  }

  /**
   * Fetches a step from the steps property.
   * If it doesn't exist, create step object.
   * @param $step_id
   * @return \Drupal\stacks\WidgetAdmin\Step\StepInterface
   */
  public function getStep($step_id) {
    if (isset($this->steps[$step_id])) {
      // If step was already initialized, use that step.
      // Chance is there are values stored on that step.
      $step = $this->steps[$step_id];
    }
    else {
      // We only call this function if the step has NOT been saved already. This
      // will load some default values from the widget instance and it might
      // skip step #1 if there are no options to select.
      $step = $this->loadStepClass($step_id);
    }

    return $step;
  }

  /**
   * If this is the first step, and if we are modifying a widget instance, we
   * need to determine if we should skip to step #2.
   *
   * If we do skip to step #2, we need to make sure to set the correct values
   * for step #1.
   */
  public function loadStepClass($step_id) {

    // Pre-populate step #1 and #2 form values if the form values have not been
    // saved yet and if we have a widget instance.
    if ($step_id == 1 && isset($_GET['widget_instance_id'])) {

      // Load the widget instance and widget entities.
      $entities = $this->getEntities($_GET['widget_instance_id']);

      // Load step #1 class.
      $class = StepsEnum::map(1);
      $step_1 = new $class($this);

      // Adds values from the widget instance to the step #1 form.
      $step_1->editWidgetInstance($entities);
      $this->addStep($step_1);

      // Load step #2 class.
      $class = StepsEnum::map(2);
      $step_2 = new $class($this);

      // Adds values from the widget instance to the step #2 form.
      $step_2->editWidgetInstance($entities);
      $this->addStep($step_2);


      // Skip to step #2?
      $step1_values = $step_1->getValues();
      if (!$step1_values['has_templates'] && !$step1_values['has_themes']) {
        // Based on the widget type they selected, there are no template or
        // theme options. Go to step #2!
        $step = $step_2;
        $step_2->setSkippedStep1();
      }
      else {
        // There are template and/or theme options for this widget type. Display
        // step #1.
        $step = $step_1;
      }

    }
    else {

      /** @var \Drupal\stacks\WidgetAdmin\Step\StepInterface $step */
      $class = StepsEnum::map($step_id);
      $step = new $class($this);

    }

    return $step;
  }

  /**
   * @returns all steps.
   */
  public function getAllSteps() {
    return $this->steps;
  }

  /**
   * Return stacks entity and widget instance entity based on widget instance id.
   *
   * @param bool $widget_instance_id
   * @return mixed
   */
  public function getEntities($widget_instance_id) {
    $widget_instance_id = (int) $widget_instance_id;
    if ($widget_instance_id < 1) {
      return FALSE;
    }

    // Load the widget instance entity.
    if (!$widget_instance = WidgetInstanceEntity::load($widget_instance_id)) {
      return FALSE;
    }

    // Load the stacks entity.
    if (!$widget_entity = $widget_instance->getWidgetEntity()) {
      return FALSE;
    }

    return [
      'widget_instance_entity' => $widget_instance,
      'widget_entity' => $widget_entity,
    ];
  }

  /**
   * Takes the widget instance template value, and returns the bundle and
   * template value.
   */
  public function extractBundleFromTemplate($widget_template) {
    if (empty($widget_template)) {
      return '';
    }

    $template_value = explode('--', $widget_template);
    return ['bundle' => $template_value[0], 'template' => $template_value[1]];
  }

}
