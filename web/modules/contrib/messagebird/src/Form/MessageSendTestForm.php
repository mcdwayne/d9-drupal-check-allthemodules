<?php

namespace Drupal\messagebird\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MessagebirdTestForm.
 *
 * @package Drupal\messagebird
 */
class MessageSendTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'messagebird_test_message_send';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['originator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Originator'),
    );

    $form['recipients'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Recipients'),
      '#description' => $this->t('Enter one or more recipients, separated by a comma.'),
      '#required' => TRUE,
    );

    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
    );

    $form['schedule'] = array(
      '#type' => 'datetime',
      '#title' => 'Scheduled date and time',
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\messagebird\MessageBirdMessage $message */
    $message = \Drupal::service('messagebird.message');

    // Set up some values for the message.
    $message
      ->setBody($form_state->getValue('body'))
      ->setOriginator($form_state->getValue('originator'));

    /** @var DrupalDateTime $datetime */
    if ($datetime = $form_state->getValue('schedule')) {
      $message->setScheduled($datetime->format(DATE_RFC3339));
    }

    // Define all the recipients.
    $recipients = explode(',', $form_state->getValue('recipients'));
    foreach ($recipients as $recipient) {
      $message->setRecipient((int) $recipient);
    }

    // Do some sending.
    $message->sendSms();

    // Validate if the message has been validated by MessageBird.
    if (empty($message->getId())) {
      drupal_set_message($this->t('Failed to send message.'), 'error');
      return;
    }

    if ($this->config('messagebird.settings')->get('debug.mode')) {
      return;
    }

    drupal_set_message(t('Message processed with id: %id', array(
      '%id' => $message->getId(),
    )));

    // Check the message status of every recipient.
    foreach ($message->getRecipients() as $number => $status) {

      // Any other status than 'sent' is not a success.
      if ($status['status'] != 'sent') {
        drupal_set_message(t('Failed to sent to %recipient with status %status', array(
          '%recipient' => $number,
          '%status' => $status['status'],
        )), 'warning');
        continue;
      }

      /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
      $date_formatter = \Drupal::service('date.formatter');
      $datetime = DrupalDateTime::createFromFormat(DATE_RFC3339, $status['status_datetime']);

      drupal_set_message($this->t('Successfully sent to %recipient on %datetime.', array(
        '%recipient' => $number,
        '%datetime' => $date_formatter->format($datetime->format('U'), 'long'),
      )), 'status');
    }
  }

}
