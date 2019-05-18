<?php

namespace Drupal\messagebird\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MessagebirdTestForm.
 *
 * @package Drupal\messagebird
 */
class MessageGetTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'messagebird_test_message_get';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message ID'),
      '#required' => TRUE,
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Get message'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\messagebird\MessageBirdMessage $message */
    $message = \Drupal::service('messagebird.message');

    // Get the Message by Id.
    $message
      ->readMessage($form_state->getValue('mid'));

    // Validate if the message has been validated by MessageBird.
    if (empty($message->getId())) {
      drupal_set_message($this->t('No message found with given Id.'), 'error');
      return;
    }

    if ($this->config('messagebird.settings')->get('debug.mode')) {
      drupal_set_message(t('Message found with given Id %id', array(
        '%id' => $form_state->getValue('mid'),
      )));
    }
  }

}
