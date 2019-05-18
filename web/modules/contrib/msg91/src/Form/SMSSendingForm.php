<?php

namespace Drupal\msg91\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a SMS Sending Form.
 */
class SMSSendingForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'smssendingform';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];
    $form['mobile_number'] = [
      '#type' => 'textfield',
      '#title' => 'Mobile Number',
      '#size' => 20,
      '#maxlength' => 150,
      '#required' => TRUE,
    ];

    $form['sms_message'] = [
      '#type' => 'textarea',
      '#title' => 'Message',
      '#size' => 256,
      '#maxlength' => 256,
      '#required' => FALSE,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send SMS Now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mobile = $form_state->getValue('mobile_number');
    if (!is_numeric($mobile)) {
      $form_state->setErrorByName('mobile_number', $this->t('Please enter valid mobile number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('msg91.settings');
    $mobile_number = $form_state->getValue('mobile_number');
    $sms_message = $form_state->getValue('sms_message');

    // Sender ID,While using route4 sender id should be 6 characters long.To be set up in settings variables.
    $sender_id = $config->get('msg91_senderID');

    // Your message to send, Add URL encoding here.
    $message = urlencode($sms_message);

    // Define route.
    $route = $config->get('msg91_route');
    // Prepare you post parameters
    // Function for sending message.
    msg91_send_message($mobile_number, $message, $sender_id, $route);

  }

}
