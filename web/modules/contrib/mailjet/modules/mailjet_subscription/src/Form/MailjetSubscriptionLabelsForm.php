<?php

/**
 * @file
 * Contains \Drupal\mailjet_subscription\Form\MailjetSubcriptionLabelsForm.
 */

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class MailjetSubscriptionLabelsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailjet_labels.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailjet_labels_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (isset($_GET['entity-id']) && !empty($_GET['entity-id'])) {
      $form_id = $_GET['entity-id'];
      $signup_form = mailjet_subscription_load($form_id);
    }

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_id,
    ];


    $form['subscribe_field_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => $signup_form->email_label,
      '#required' => TRUE,
    ];

    $fields = explode(',', $signup_form->fields_mailjet);
    $labels_fields = explode(',', $signup_form->labels_fields);
    $counter = 0;

    if (!(empty($fields[0]))) {
      foreach ($fields as $field) {
        $form['singup-' . $field] = [
          '#type' => 'textfield',
          '#title' => t('Label of \'machine name\' field: ' . $field),
          '#description' => '',
          '#required' => TRUE,
          '#default_value' => $labels_fields[$counter],
        ];

        $counter++;
      }
    }

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = mailjet_subscription_load($form_state->getValue('entity_id'));
    $entity->set('email_label', $form_state->getValue('subscribe_field_email'));

    $fields = explode(',', $entity->fields_mailjet);
    $fields_label = [];

    if (!(empty($fields[0]))) {
      foreach ($fields as $field) {
        $label = !empty($form_state->getValue('singup-' . $field)) ? $form_state->getValue('singup-' . $field) : '';
        array_push($fields_label, $label);

      }

      $entity->set('labels_fields', implode(',', $fields_label));
    }

    $status = $entity->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Subscription Form Labels have been updated.'));
    }
    else {
      drupal_set_message(t('Subscription Form Labels haven\'t been updated.'));
    }

  }

}
