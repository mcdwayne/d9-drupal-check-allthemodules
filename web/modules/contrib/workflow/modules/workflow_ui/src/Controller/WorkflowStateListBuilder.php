<?php

namespace Drupal\workflow_ui\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Defines a class to build a draggable listing of Workflow State entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowState
 */
class WorkflowStateListBuilder extends DraggableListBuilder {

  /**
   * Load the Transitions, and filter for Workflow type.
   * {@inheritdoc}
   */
  public function load() {
    $entities = [];

    if (!$workflow = workflow_url_get_workflow()) {
      return $entities;
    }

    $wid = $workflow->id();
    /** @var WorkflowState[] $entities */
    $entities = parent::load();
    foreach ($entities as $key => $entity) {
      if ($entity->getWorkflowId() != $wid) {
        unset($entities[$key]);
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_state_form';
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    // The column 'weight' is added magically in the draggable EntityList.
    //  $header['weight'] = $this->t('Weight');
    // Some columns are not welcome in the list.
    //  $header['module'] = $this->t('Module');
    //  $header['wid'] = $this->t('Workflow');
    //  $header['sysid'] = $this->t('Sysid');
    // Column 'label' is manipulated in parent::buildForm(). So, we use 'label_new'.
    //  $header['label'] = $this->t('Label');
    $header['label_new'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['sysid'] = '';
    $header['status'] = $this->t('Active');
    $header['reassign'] = $this->t('Reassign');
    $header['count'] = $this->t('Count');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    if (!$workflow = workflow_url_get_workflow()) {
      return $row;
    }

    $wid = $url_wid = $workflow->id();
    /** @var $entity \Drupal\workflow\Entity\WorkflowState */
    $state = $entity;
    $sid = $state->id();
    $label = $state->label();
    $count = $state->count();

    // Build select options for reassigning states.
    // We put a blank state first for validation.
    $state_options = ['' => ' '];
    $state_options += workflow_get_workflow_state_names($wid, FALSE);

    // Make it impossible to reassign to the same state that is disabled.
    $current_state_options = [];
    if ($state->isActive() && !$state->isCreationState() && $sid) {
      $current_state = [$sid => $state_options[$sid]];
      $current_state_options = array_diff($state_options, $current_state);
    }

    /*
     *  Build the Row.
     */
    // The column 'weight' is added magically in the draggable EntityList.
    //  $row['weight'] = $state->weight;
    // Some columns are not welcome in the list.
    //  $row['module'] = $state->getModule();
    //  $row['wid'] = $state->getWorkflow();
    // Column 'label' is manipulated in parent::buildForm(). So, we use 'label_new'.
    //  $row['label'] = $state->label();
    $row['label_new'] = [
      '#markup' => $label,
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => $label,
      '#title' => NULL, // This hides the red 'required' asterisk.
      '#disabled' => !$state->isActive(),
      // '#required' => TRUE,
    ];
    $row['id'] = [
      '#type' => 'machine_name',
      '#title' => NULL, // This hides the red 'required' asterisk.
      '#size' => 30,
      '#description' => NULL,
      '#disabled' => TRUE, // !$state->isNew(),
      '#default_value' => $state->id(),
      // N.B.: Keep machine_name in WorkflowState and ~ListBuilder aligned.
      '#required' => FALSE,
      // @TODO D8-port: enable machine_name as interactive WorkflowState element.
      '#machine_name' => [
        'exists' => [$this, 'exists'], // Local helper function, at the bottom of this class.
        // 'source' => ['label_new'],
        'source' => ['states', $state->id(), 'label_new'],
        // 'replace_pattern' =>'([^a-z0-9_]+)|(^custom$)',
        'replace_pattern' => '[^a-z0-9_()]+', // Added '()' characters from exclusion list since creation state has it.
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores.'),
      ],
    ];
    $row['sysid'] = [
      '#type' => 'value',
      '#value' => $state->sysid,
    ];
    $row['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $state->isActive(),
      '#disabled' => $state->isCreationState() || !$sid,
    ];
    // The new value of states that are inactivated.
    $row['reassign'] = [
      '#type' => 'select',
      '#options' => $current_state_options,
    ];
    $row['count'] = [
      '#type' => 'value',
      '#value' => $count,
      '#markup' => $count,
    ];

    // New state and disabled states cannot be reassigned.
    if (!$state->isActive() || $state->isCreationState() || !$sid || !$count) {
      $row['reassign']['#type'] = 'hidden';
      $row['reassign']['#disabled'] = TRUE;
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (!$workflow = workflow_url_get_workflow()) {
      return $form;
    }

    $form = parent::buildForm($form, $form_state);
    // Add a sticky header.
    $form[$this->entitiesKey] += [
      '#sticky' => TRUE,
    ];

    $wid = $workflow->id();
    // Build select options for reassigning states.
    // We put a blank state first for validation.
    $state_options = workflow_get_workflow_state_names($wid, FALSE);
    // Is this the last state available?
    $form['#last_mohican'] = (count($state_options) == 1);

    $form['entities']['#prefix'] = '<div id="states_table_wrapper">';
    $form['entities']['#suffix'] = '</div>';
    // Create a placeholder WorkflowState (It must NOT be saved to DB). Add it to the item list.
    if ($form_state->getTriggeringElement()['#name'] === 'add_state') {
      $sid = '';
      $placeholder = $workflow->createState($sid, FALSE);
      $placeholder->set('label', '');
      $this->entities['placeholder'] = $placeholder;
      $form['entities']['placeholder'] = $this->buildRow($placeholder);
    }
    // Rename 'submit' button.
    $form['actions']['submit']['#value'] = t('Save');
    // Add 'Add State' button.
    $form['actions']['add_state'] = [
      '#name' => 'add_state',
      '#type' => 'submit',
      '#value' => t('Add State'),
      '#ajax' => [
        'callback' => '::addStateCallback',
        'wrapper' => 'states_table_wrapper',
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    /** @var $state \Drupal\workflow\Entity\WorkflowState */
    $state = $entity;

    /**
     * Allow modules to insert their own workflow operations to the list.
     */
    // This is what EntityListBuilder::getOperations() does:
    // $operations = $this->getDefaultOperations($entity);
    // $operations += $this->moduleHandler()->invokeAll('entity_operation', [$entity]);
    // $this->moduleHandler->alter('entity_operation', $operations, $entity);

    // In D8, the interface of below hook_workflow_operations has changed a bit.
    // @see EntityListBuilder::getOperations, workflow_operations, workflow.api.php.
    $operations += $this->moduleHandler()->invokeAll('workflow_operations', ['state', $state]);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO D8-port WorkflowStateListBuilder: enable machine_name as interactive WorkflowState element.
    foreach ($form_state->getValue($this->entitiesKey) as $sid => $value) {
      /** @var $state WorkflowState */
      $state = isset($this->entities[$sid]) ? $this->entities[$sid] : NULL;

      // State is de-activated (reassigning current content).
      if ($state && $state->isActive() && !$value['status']) {
        $args = ['%state' => $state->label()];
        // Does that state have content in it?
        if (!$form['#last_mohican'] && $value['count'] > 0 && empty($value['reassign'])) {
          $message = 'The %state state has content; you must
              reassign the content to another state.';
          $form_state->setErrorByName("states'][$sid]['reassign'", $this->t($message, $args));
        }
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Overrides DraggableListBuilder::submitForm().
   * The WorkflowState entities are always saved.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (!$workflow = workflow_url_get_workflow()) {
      return [];
    }

    // The default min_weight is -10. Work with it.
    $creation_weight = -11;
    $max_weight = -9;
    foreach ($form_state->getValue($this->entitiesKey) as $sid => $value) {
      if (isset($this->entities[$sid])) {
        /** @var $state WorkflowState */
        $state = $this->entities[$sid];

        // Is the new state name empty?
        if (empty($value['label_new'])) {
          // No new state entered, so skip it.
          continue;
        }

        // Does user want to deactivate the state (reassign current content)?
        if ($state && $state->isActive() && !$value['status'] && $sid) {
          $new_sid = $value['reassign'];
          $new_state = WorkflowState::load($new_sid);

          $args = [
            '%workflow' => $workflow->label(),
            '%old_state' => $state->label(),
            '%new_state' => isset($new_state) ? $new_state->label() : '',
          ];

          if ($value['count'] > 0) {
            if ($form['#last_mohican']) {
              $new_sid = NULL; // Do not reassign to new state.
              $message = 'Removing workflow states from content in the %workflow.';
              drupal_set_message($this->t($message, $args));
              $message = 'Since you have deleted the last available
                workflow state in this workflow, all content items
                which with this %workflow workflow have their workflow state
                removed.';
              drupal_set_message($this->t($message, $args), 'warning');
            }
            else {
              // Prepare the state delete function.
              $message = 'Reassigning content from %old_state to %new_state.';
              drupal_set_message($this->t($message, $args));
            }
          }
          // Delete the old state without orphaning content, move them to the new state.
          $state->deactivate($new_sid);

          $message = $this->t('Deactivated workflow state %old_state in %workflow.', $args);
          \Drupal::logger('workflow')->notice($message, []);
          drupal_set_message($message);
        }

        // Set a proper weight to the new state.
        $max_weight = max($max_weight, $state->get($this->weightKey));

        // Is this a new state?
        if ($sid === 'placeholder' && empty(!$value['label_new'])) {
          // New state, add it.
          $state->set('id', $value['id']);
          // Set a proper weight to the new state.
          $state->set($this->weightKey, $max_weight + 1);
        }
        elseif ($value['sysid'] == WORKFLOW_CREATION_STATE) {
          // Set a proper weight to the creation state.
          $state->set($this->weightKey, $creation_weight);
        }
        else {
          $state->set($this->weightKey, $value['weight']);
        }
        $state->set('label', $value['label_new']);
        $state->set('status', $value['status']);

        try {
          $state->save();
        } catch(\Exception $e) {
          $message = 'ID %id already exists';
          $args = ['%id' => $state->id()];
          return drupal_set_message($this->t($message, $args), 'error');
        }
      }
    }

    if ($form_state->getTriggeringElement()['#name'] === 'add_state') {
      // Unset previous input in placeholder and rebuild the form.
      $input = $form_state->getUserInput();
      unset($input['entities']['placeholder']['label_new']);
      $form_state->setUserInput($input);
      $form_state->setRebuild();
    }

    return drupal_set_message($this->t('The Workflow states have been updated.'));
  }

  /**
   * 'Add State' button callback.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return
   */
  public function addStateCallback(array &$form, FormStateInterface $form_state) {
    return $form['entities'];
  }

  /**
   * Validate duplicate machine names.
   * Function registered in 'machine_name' form element.
   *
   * @param string $name
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  function exists($name, array $element, FormStateInterface $form_state) {
    $state_names = [];
    foreach ($form_state->getValue($this->entitiesKey) as $sid => $value) {
      $state_names[] = $value['id'];
    }
    $state_names = array_map('strtolower', $state_names);
    $result = array_unique(array_diff_assoc($state_names, array_unique($state_names)));

    if (in_array($name, $result)) {
      return TRUE;
    }
    return FALSE;
  }
}
