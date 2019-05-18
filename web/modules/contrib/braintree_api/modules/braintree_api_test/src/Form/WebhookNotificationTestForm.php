<?php

namespace Drupal\braintree_api_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WebhookNotificationTestForm.
 */
class WebhookNotificationTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webhook_notification_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['bt_signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BT Signature'),
    ];

    $form['bt_payload'] = [
      '#type' => 'textarea',
      '#title' => $this->t('BT Payload'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['#action'] = '/braintree/webhooks';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      $this->messenger()->addStatus($key . ': ' . $value);
    }
  }

}
