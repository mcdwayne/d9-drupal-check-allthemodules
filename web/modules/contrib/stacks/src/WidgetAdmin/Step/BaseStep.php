<?php

namespace Drupal\stacks\WidgetAdmin\Step;

use Drupal\stacks\WidgetAdmin\Manager\StepManager;
use Drupal\stacks\Entity\WidgetInstanceEntity;

/**
 * Class BaseStep
 * @package Drupal\stacks\WidgetAdminStep
 */
abstract class BaseStep implements StepInterface {

  protected $step;
  protected $values;
  protected $stepManager;
  protected $options;

  /**
   * BaseStep constructor.
   *
   * @param \Drupal\stacks\WidgetAdmin\Manager\StepManager $stepManager
   */
  public function __construct(StepManager $stepManager) {
    $this->stepManager = $stepManager;

    // What is this?
    $this->step = $this->setStep();
  }

  /**
   * @inheritdoc
   */
  public function getStep() {
    return $this->step;
  }

  /**
   * @inheritdoc
   */
  public function isLastStep() {
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function setValues($values) {
    $this->values = $values;
  }

  /**
   * @inheritdoc
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * @inheritDoc.
   */
  public function getFieldNames() {
    return [];
  }

  /**
   * @inheritDoc.
   */
  public function getFieldsValidators() {
    return [];
  }

  /**
   * @inheritDoc.
   */
  public function getStepValues($step = 1) {
    return $this->stepManager->getStep($step)->getValues();
  }
}
