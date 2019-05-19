<?php

namespace Drupal\workflow_state_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Class WorkflowStateConfigForm.
 */
class WorkflowStateConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_state_config_form';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $arrSatate = [];
    // Load all the state of workflow.
    $workflowId     = \Drupal::request()->get('workflow_type');
    $workflow       = Workflow::load($workflowId);
    $workflowStates = $workflow->getStates();
    foreach ($workflowStates as $state) {
      $arrSatate[$state->id] = [
        'state_name' => '<span class="table-filter-text-source">' . $state->label . '</span>',
        'state_id' => $state->id,
      ];
    }
    $form['desc']                 = [
      '#type' => 'markup',
      '#markup' => '<p><strong>Published</strong> - When content reaches this state it should be published.</br><strong>DefaultRevision</strong> - When content reaches this state it should be made the default revision; this is implied for published states.</p>',
    ];
    $form['states']['#type']      = 'table';
    $form['states']['#js_select'] = TRUE;
    $form['states']['#header']    = [
      'State Name',
      'State Id',
      'Published',
      'Default Revision',
    ];
    foreach ($arrSatate as $key => $value) {
      $workflowStateConfig = WorkflowState::load($value['state_id']);
      $arr = [
        'state_name' => [
          '#markup'   => $value['state_name'],
        ],
        'state_id' => [
          '#type'       => 'textfield',
          '#attributes' => [ 'readonly' => 'readonly' ],
          '#default_value' => $value['state_id'],
        ],
        'node_status' => [
          '#type'   => 'checkbox',
          '#title'  => '',
          '#default_value' => (isset($workflowStateConfig->node_status)) ? $workflowStateConfig->node_status : '',
        ],
        'node_default_revision' => [
          '#type'   => 'checkbox',
          '#title'  => '',
          '#default_value' => (isset($workflowStateConfig->node_default_revision)) ? $workflowStateConfig->node_default_revision : '',
        ],
      ];
      $form['states'][$key] = $arr;
    }
    $form['actions'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Save'),
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $arrState = $form_state->getValues()['states'];
    foreach ($arrState as $state) {
      $configVariable = 'workflow.state.' . $state['state_id'];
      $getStateConfig = \Drupal::configFactory()->getEditable($configVariable);
      $getStateConfig->set('node_status', $state['node_status']);
      $getStateConfig->set('node_default_revision', $state['node_default_revision']);
      $getStateConfig->save();
    }
    drupal_set_message('The Workflow states configuration have been updated');

  }

}
