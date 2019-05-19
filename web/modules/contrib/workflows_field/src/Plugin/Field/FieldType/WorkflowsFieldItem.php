<?php

namespace Drupal\workflows_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;

/**
 *   constraints = {"WorkflowsFieldValidStateTransition" = {}}
 *
 * @FieldType(
 *   id = "workflows_field_item",
 *   label = @Translation("Workflows"),
 *   description = @Translation("Allows you to store a workflow state."),
 *   constraints = {"WorkflowsFieldConstraint" = {}},
 *   default_formatter = "list_default",
 *   default_widget = "options_select"
 * )
 */
class WorkflowsFieldItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('State'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 64,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = [
      'workflow' => NULL,
    ];
    return $settings + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $workflows = Workflow::loadMultipleByType('workflows_field');
    $options = [];
    foreach ($workflows as $workflow) {
      $options[$workflow->id()] = $workflow->label();
    }
    $element = [];
    $element['workflow'] = [
      '#title' => $this->t('Workflow'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('workflow'),
      '#type' => 'select',
      '#options' => $options,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $workflow = $this->getWorkflow();
    if (!$workflow) {
      // The workflow is not known yet, the field is probably being created.
      return [];
    }
    $state_labels = array_map(function ($state) {
      return $state->label();
    }, $workflow->getTypePlugin()->getStates());

    return $state_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // $this->value is unpopulated due to https://www.drupal.org/node/2629932
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;

    $workflow = $this->getWorkflow();
    $type = $workflow->getTypePlugin();
    $allowed_states = $type->getStates();

    /** @var \Drupal\workflows\State $current */
    if ($value && $type->hasState($value) && ($current = $type->getState($value))) {
      $allowed_states = array_filter($allowed_states, function(StateInterface $state) use ($current, $workflow, $account) {
        if ($current->id() === $state->id()) {
          return TRUE;
        }

        // If we don't have a valid transition or we don't have an account then
        // all we care about is whether the transition is valid so return.
        $valid_transition = $current->canTransitionTo($state->id());
        if (!$valid_transition || !$account) {
          return $valid_transition;
        }

        // If we have an account object then ensure the user has permission to
        // this transition and that it's a valid transition.
        $transition = $current->getTransitionTo($state->id());
        return $account->hasPermission(sprintf('use %s transition %s', $workflow->id(), $transition->id()));
      });
    }

    $state_labels = array_map(function ($state) {
      return $state->label();
    }, $allowed_states);

    return $state_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    if ($workflow = $this->getWorkflow()) {
      $initial_state = $workflow->getTypePlugin()->getInitialState();
      $this->setValue(['value' => $initial_state->id()], $notify);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(FieldStorageDefinitionInterface $field_definition) {
    $dependencies['config'][] = sprintf('workflows.workflow.%s', $field_definition->getSetting('workflow'));
    return $dependencies;
  }

  /**
   * Gets the workflow associated with this field.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   The workflow of NULL.
   */
  public function getWorkflow() {
    return !empty($this->getSetting('workflow')) ? Workflow::load($this->getSetting('workflow')) : NULL;
  }

}
