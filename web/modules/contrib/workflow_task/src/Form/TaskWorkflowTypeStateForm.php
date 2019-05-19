<?php

namespace Drupal\workflow_task\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeStateFormBase;
use Drupal\workflows\StateInterface;

/**
 * The TaskWorkflowType state form.
 *
 * @see \Drupal\workflow_task\Plugin\WorkflowType\TaskWorkflowType
 */
class TaskWorkflowTypeStateForm extends WorkflowTypeStateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, StateInterface $state = NULL) {
    return $form;
  }

}
