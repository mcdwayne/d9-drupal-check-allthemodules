<?php

namespace Drupal\workflow_task\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WorkflowTaskTypeForm.
 */
class WorkflowTaskTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $workflow_task_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workflow_task_type->label(),
      '#description' => $this->t("Label for the Workflow task type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $workflow_task_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\workflow_task\Entity\WorkflowTaskType::load',
      ],
      '#disabled' => !$workflow_task_type->isNew(),
    ];

    $workflows = $this->entityTypeManager->getStorage('workflow')
      ->loadByProperties(['type' => 'workflow_task']);

    $workflowOptions = [];
    foreach ($workflows as $workflow) {
      $workflowOptions[$workflow->id()] = $workflow->label();
    }

    $form['workflows'] = [
      '#title' => $this->t('Allowed workflows'),
      '#type' => 'checkboxes',
      '#options' => $workflowOptions,
      '#default_value' => $workflow_task_type->getWorkflowIds(),
      '#required' => TRUE,
    ];

    $form['default_workflow'] = [
      '#title' => $this->t('Default workflow'),
      '#type' => 'select',
      '#options' => $workflowOptions,
      '#default_value' => $workflow_task_type->getDefaultWorkflowId(),
      '#required' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workflow_task_type = $this->entity;
    $status = $workflow_task_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Workflow task type.', [
          '%label' => $workflow_task_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Workflow task type.', [
          '%label' => $workflow_task_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($workflow_task_type->toUrl('collection'));
  }

}
