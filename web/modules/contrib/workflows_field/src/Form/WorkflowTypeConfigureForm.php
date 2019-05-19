<?php

namespace Drupal\workflows_field\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeConfigureFormBase;
use Drupal\workflows\State;

/**
 * Plugin form for the workflows field.
 */
class WorkflowTypeConfigureForm extends WorkflowTypeConfigureFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->workflowType->getConfiguration();
    $form['settings'] = [
      '#title' => $this->t('Workflow Settings'),
      '#type' => 'fieldset',
    ];
    $labels = array_map([State::class, 'labelCallback'], $this->workflowType->getStates());
    $form['settings']['initial_state'] = [
      '#title' => $this->t('Initial State'),
      '#type' => 'select',
      '#default_value' => isset($configuration['initial_state']) ? $configuration['initial_state'] : NULL,
      '#options' => $labels,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->workflowType->getConfiguration();
    $configuration['initial_state'] = $form_state->getValue(['settings', 'initial_state']);
    $this->workflowType->setConfiguration($configuration);
  }

}
