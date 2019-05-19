<?php

namespace Drupal\simple_multistep;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormStep.
 *
 * @package Drupal\simple_multistep
 */
class FormStep {

  /**
   * Form array.
   *
   * @var array
   */
  protected $form;

  /**
   * Form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Current step.
   *
   * @var int
   */
  protected $currentStep;

  /**
   * Steps.
   *
   * @var array
   */
  protected $steps;

  /**
   * Step settings.
   *
   * @var object
   */
  protected $stepSettings;

  /**
   * FormStepController constructor.
   *
   * @param array $form
   *   Form settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function __construct(array &$form, FormStateInterface $form_state) {
    $this->form = $form;
    $this->formState = $form_state;

    $this->currentStep = 0;
    $this->updateStepInfo();
  }

  /**
   * Update step info.
   */
  public function updateStepInfo() {
    // Fetch list all steps.
    $this->fetchSteps();

    // Fetch current step settings.
    $this->fetchStepSettings();
  }

  /**
   * Get current step.
   *
   * @return int
   *   Current step.
   */
  public function getCurrentStep() {
    return $this->currentStep;
  }

  /**
   * Increase step number.
   */
  public function increaseStep() {
    $this->currentStep++;
  }

  /**
   * Reduce step number.
   */
  public function reduceStep() {
    $this->currentStep--;
  }

  /**
   * Set current step.
   */
  protected function setCurrentStep() {
    $this->currentStep = 0;

    $this->currentStep = empty($this->formState->get('step')) ? 0 : $this->formState->get('step');
  }

  /**
   * Get all form steps.
   */
  public function getSteps() {
    return $this->steps;
  }

  /**
   * Get array with form steps.
   */
  protected function fetchSteps() {
    $steps = [];

    if (isset($this->form['#fieldgroups']) && is_array($this->form['#fieldgroups'])) {
      foreach ($this->form['#fieldgroups'] as $field_group) {
        if ($field_group->format_type == 'form_step') {
          $steps[] = $field_group;
        }
      }
      usort($steps, [$this, 'sortStep']);
    }

    $this->steps = $steps;
  }

  /**
   * Sort array by object property.
   *
   * @param object $first_object
   *   First object.
   * @param object $second_object
   *   Second object.
   *
   * @return int
   *   Indicator.
   */
  protected static function sortStep($first_object, $second_object) {
    if ($first_object->weight == $second_object->weight) {
      return 0;
    }
    return ($first_object->weight < $second_object->weight) ? -1 : 1;
  }

  /**
   * Get form step settings.
   */
  public function getStepSettings() {
    return $this->stepSettings;
  }

  /**
   * Fetch form step settings by current step.
   */
  protected function fetchStepSettings() {
    $step_settings = [];
    if (isset($this->form['#fieldgroups'])) {
      $form_steps = $this->getSteps();

      if (!empty($form_steps) && isset($form_steps[$this->currentStep])) {
        $step_settings = $form_steps[$this->currentStep];
      }
    }

    $this->stepSettings = $step_settings;
  }

  /**
   * Set $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function setFormState(FormStateInterface $form_state) {
    $this->formState = $form_state;
  }

  /**
   * Get submission values for current step.
   *
   * @param object $step
   *   Step option.
   *
   * @return array
   *   Array with step values.
   */
  public function getStepValues($step) {
    $list_value = [];
    $all_children = $this->getAllChildren($step);
    $current_user_input = $this->formState->getValues();

    if (in_array('account', $all_children)) {
      $all_children = array_merge($all_children, ['name', 'pass', 'mail']);
    }

    foreach ($all_children as $field_name) {
      if (isset($current_user_input[$field_name])) {
        $list_value[$field_name] = $current_user_input[$field_name];
      }
    }
    return $list_value;
  }

  /**
   * Get all child from field group.
   *
   * @param object $fieldgroup
   *   Field group object.
   * @param array $child
   *   Array with existing child.
   *
   * @return array
   *   Return array with child.
   */
  protected function getAllChildren($fieldgroup, array $child = []) {
    if ($group_children = $fieldgroup->children) {
      foreach ($group_children as $form_element_id) {
        if (isset($this->form[$form_element_id])) {
          $child[] = $form_element_id;
        }
        elseif (isset($this->form['#fieldgroups'][$form_element_id]->children)) {
          $child = $this->getAllChildren($this->form['#fieldgroups'][$form_element_id], $child);
        }
      }
    }

    return $child;
  }

}
