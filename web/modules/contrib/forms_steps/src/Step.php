<?php

namespace Drupal\forms_steps;

/**
 * A value object representing a step state.
 */
class Step implements StepInterface {

  /**
   * The forms_steps the step is attached to.
   *
   * @var \Drupal\forms_steps\FormsStepsInterface
   */
  protected $formsSteps;

  /**
   * The step's ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The step's label.
   *
   * @var string
   */
  protected $label;

  /**
   * The step's weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The step's entity_type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The step's entity_bundle.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * The step's form_view_mode_id.
   *
   * @var string
   */
  protected $formMode;

  /**
   * The step's URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The step's submit label.
   *
   * @var string
   */
  protected $submitLabel;

  /**
   * The step's cancel label.
   *
   * @var string
   */
  protected $cancelLabel;

  /**
   * The step's delete label.
   *
   * @var string
   */
  protected $deleteLabel;

  /**
   * The step's cancel route.
   *
   * @var string
   */
  protected $cancelRoute;

  /**
   * The step's cancel step.
   *
   * @var \Drupal\forms_steps\Step
   */
  protected $cancelStep;

  /**
   * The step's cancel step mode.
   *
   * @var string
   */
  protected $cancelStepMode;

  /**
   * The step's delete state.
   *
   * @var bool
   */
  protected $hideDelete;

  /**
   * The step's previous label.
   *
   * @var string
   */
  protected $previousLabel;

  /**
   * The step's previous state.
   *
   * @var bool
   */
  protected $displayPrevious;

  /**
   * Step constructor.
   *
   * @param \Drupal\forms_Steps\FormsStepsInterface $forms_steps
   *   The forms_steps the step is attached to.
   * @param string $id
   *   The step's ID.
   * @param string $label
   *   The step's label.
   * @param int $weight
   *   The step's weight.
   * @param string $entityType
   *   The step's entity type.
   * @param string $entityBundle
   *   The step's bundle.
   * @param string $formMode
   *   The step's form mode.
   * @param string $url
   *   The step's URL.
   */
  public function __construct(FormsStepsInterface $forms_steps, $id, $label, $weight, $entityType, $entityBundle, $formMode, $url) {
    $this->formsSteps = $forms_steps;
    $this->id = $id;
    $this->label = $label;
    $this->weight = $weight;
    $this->entityType = $entityType;
    $this->entityBundle = $entityBundle;
    $this->formMode = $formMode;
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function entityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function entityBundle() {
    return $this->entityBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function formMode() {
    return $this->formMode;
  }

  /**
   * {@inheritdoc}
   */
  public function url() {
    return $this->url;
  }

  /**
   * Return a list of form modes available for this entity bundle.
   *
   * @return array
   *   Returns the list of form modes.
   */
  public function formModes() {
    $result = [];

    // Get the list of available form modes for a certain entity type.
    $form_modes = \Drupal::entityManager()->getFormModes($this->entityType);

    foreach ($form_modes as $form_mode) {
      $result[$form_mode['id']] = $form_mode['label'];
    }

    $result['default'] = 'Default';

    return $result;
  }

  /**
   * Gets the submit label.
   *
   * @return string
   *   The submit label.
   */
  public function submitLabel() {
    return $this->submitLabel;
  }

  /**
   * Gets the cancel label.
   *
   * @return string
   *   The cancel label.
   */
  public function cancelLabel() {
    return $this->cancelLabel;
  }

  /**
   * Gets the delete label.
   *
   * @return string
   *   The delete label.
   */
  public function deleteLabel() {
    return $this->deleteLabel;
  }

  /**
   * Gets the cancel route.
   *
   * @return string
   *   The cancel route.
   */
  public function cancelRoute() {
    return $this->cancelRoute;
  }

  /**
   * Gets the cancel step.
   *
   * @return \Drupal\forms_steps\Step
   *   The cancel step.
   */
  public function cancelStep() {
    return $this->cancelStep;
  }

  /**
   * Get the hidden status of the delete button.
   *
   * @return bool
   *   TRUE if hidden | FALSE otherwise
   */
  public function hideDelete() {
    return $this->hideDelete;
  }

  /**
   * Set the hidden state of the delete button.
   *
   * @param bool $value
   *   TRUE if hidden | FALSE otherwise.
   */
  public function setHideDelete($value) {
    $this->hideDelete = $value;
  }

  /**
   * Gets the cancel step mode.
   *
   * @return string
   *   The cancel step mode.
   */
  public function cancelStepMode() {
    return $this->cancelStepMode;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitLabel($label) {
    $this->submitLabel = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelLabel($label) {
    $this->cancelLabel = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeleteLabel($label) {
    $this->deleteLabel = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelRoute($route) {
    $this->cancelRoute = $route;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelStep(Step $step) {
    $this->cancelStep = $step;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelStepMode($mode) {
    $this->cancelStepMode = $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function formsSteps() {
    return $this->formsSteps;
  }

  /**
   * {@inheritdoc}
   */
  public function displayPrevious() {
    return $this->displayPrevious;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousLabel($label) {
    $this->previousLabel = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function previousLabel() {
    return $this->previousLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayPrevious($value) {
    return $this->displayPrevious = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isLast() {
    $last_step = $this->formsSteps->getLastStep();

    if ($this->id == $last_step->id()) {
      return TRUE;
    }

    return FALSE;
  }

}
