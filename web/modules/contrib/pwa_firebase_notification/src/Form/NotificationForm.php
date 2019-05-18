<?php

namespace Drupal\pwa_firebase_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pwa_firebase_notification\Controller\NotificationController;

/**
 * Custom Notification Form.
 */
class NotificationForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'pwa_firebase_notification_form';
  }

  /**
   * Form build.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['title'] = [
      "#type" => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      "#maxlength" => 100,
    ];

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      "#maxlength" => 255,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Send notification',
      ]
    ];

    return $form;
  }

  /**
   * Function saves all values and sends the message.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    NotificationController::sendMessageToAllUsers(
      $form_state->getValue('title'),
      $form_state->getValue('message')
    );
  }

}
