<?php

namespace Drupal\rules_scheduler\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Entity\RulesComponentConfig;

/**
 * Form for scheduling tasks by component.
 */
class SchedulerForm extends SchedulerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rules_scheduler_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $rules_components = array_map(function (RulesComponentConfig $config) {
      return $config->id();
    }, $this->componentStorage->loadMultiple());

    $result = $this->database->select('rules_scheduler', 'r')
      ->fields('r', ['config'])
      ->distinct()
      ->execute();

    $config_options = array_intersect_key($rules_components, $result->fetchAllAssoc('config'));

    // Fieldset for canceling by component name.
    $form['delete_by_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Delete tasks by component name'),
      '#disabled' => empty($config_options),
    ];
    $form['delete_by_config']['config'] = [
      '#title' => $this->t('Component'),
      '#type' => 'select',
      '#options' => $config_options,
      '#description' => $this->t('Select the component for which to delete all scheduled tasks.'),
      '#required' => TRUE,
    ];
    $form['delete_by_config']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete tasks'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delegate the deletion to the 'schedule_delete' action.
    $action = $this->actionManager->createInstance('schedule_delete')
      ->setContextValue('component', $this->getTypedData('string', $form_state->getValue('config')))
      ->execute();

    $this->messenger()->addMessage($this->t('All scheduled tasks associated with %config have been deleted.', ['%config' => 'this config']));
    $form_state->setRedirect('rules_scheduler.schedule');
  }

}
