<?php

namespace Drupal\workflow_sms_notify\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\WorkflowTypeAttributeTrait;
use Drupal\workflow_sms_notify\Entity\WorkflowSmsNotify;
use Drupal\workflow_notifications\Form\WorkflowNotificationForm;
use Drupal\Core\Entity\EntityForm;

/**
 * Class WorkflowNotificationForm
 */
class WorkflowSmsNotificationForm extends WorkflowNotificationForm {
  /*
   * Add variables and get/set methods for Workflow property.
   */
  use WorkflowTypeAttributeTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state); 
    $role_options = workflow_get_user_role_names('');
    $workflow_sms_notification = $this->entity;
    unset($role_options['anonymous']);
     $form['sms'] = [
       '#type' => 'fieldset',
       '#title' => t('sms'),
       '#collapsible' => TRUE,
       '#weight' => 10,
     ];
     $form['sms']['roles'] = [
       '#type' => 'checkboxes',
       '#options' => $role_options,
       '#title' => t('Roles'),
       '#default_value' => $workflow_sms_notification->roles,
       '#description' => t('Check each role that must be informed.'),
     ];
     // @todo: add validation for phone number.
     $form['sms']['phone_num'] = [
       '#type' => 'textarea',
       '#title' => t('Phone Number'),
       '#default_value' => $workflow_sms_notification->phone_num,
       '#description' => t('Enter a valid Email address, one per line.'),
     ];
     unset($form['template']['subject']);
     $form['template']['#weight'] = 11;
     $form['tokens']['#weight'] = 12;
     $form['note']['#weight'] = 13;
     unset($form['mail_to']);
    return $form;
  }
   /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    if ($form_values['when_to_trigger'] == 'on_state_change') {
      $form_state->setValue('days', 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workflow_sms_notification = $this->entity;
    $status = parent::save($form, $form_state);

    $form_state->setRedirect('entity.workflow_sms_notify.collection', ['workflow_type' => $this->getWorkflowId()]);
  }

  /**
   * Helper function for machine_name element.
   *
   * @param $id
   *   The given machine name.
   * @return bool
   *   Indicates if the machine name already exists.
   */
  public function exists($id) {
    $type = $this->entity->getEntityTypeId();
    return (bool) $this->entityTypeManager->getStorage($type)->load($id);
  }
}