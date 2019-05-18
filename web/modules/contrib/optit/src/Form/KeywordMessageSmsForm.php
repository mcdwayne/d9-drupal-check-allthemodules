<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\optit\Optit\Keyword;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that configures optit settings.
 */
class KeywordMessageSmsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_keywords_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL, $phone = NULL, $interest_id = NULL) {
    $optit = Optit::create();
    $keywords = $optit->keywordsGet();
    $options = array();
    foreach ($keywords as $keyword) {
      $options[$keyword->get('id')] = $keyword->get('keyword_name');
    }
    if (!$keyword_id) {
      $form['keyword_id'] = array(
        '#title' => t('Keyword'),
        '#description' => t('Please choose a keyword.'),
        '#type' => 'select',
        '#options' => $options,
        '#required' => TRUE,
      );
    }
    else {
      $form['keyword_id'] = array(
        '#type' => 'value',
        '#value' => $keyword_id,
        // Okay, following line is really ugly, but I need it for cleaner validation, otherwise, I'd have to run two optit queries.
        '#options' => $options,
      );
    }
    $form['interest_id'] = array(
      '#type' => 'value',
      '#value' => $interest_id,
    );
    $form['phone'] = array(
      '#type' => 'value',
      '#value' => $phone,
    );
    $form['title'] = array(
      '#title' => t('Title'),
      '#description' => t('Please enter a title of message. This does not appear in the text message and is just used in the application as a short description of your message.'),
      '#type' => 'textfield',
      '#required' => TRUE,
    );
    $form['message'] = array(
      '#title' => t('Message'),
      '#description' => t('Please enter a text message. The message must be less than 160 characters including your keyword in the beginning of the message.'),
      '#type' => 'textarea',
      '#required' => TRUE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
    );
    $form['bulk'] = array(
      '#type' => 'submit',
      '#value' => t('Add to bulk'),
      '#submit' => ['::addToBulk'],
      '#weight' => 10,
    );

    return $form;
  }


  function validateForm(array &$form, FormStateInterface $form_state) {
    $message = $form_state->getValue('message');

    // Make sure keyword and message are shorter than 160.
    $keyword_id = $form_state->getValue('keyword_id');
    $keyword = $form['keyword_id']['#options'][$keyword_id];
    $length = strlen($keyword . ': ' . $message);
    if ($length > 160) {
      $form_state->setErrorByName('message', $this->t('The message must be less than 160 characters including your keyword in the beginning of the message. Your message has :length characters', array(':length' => $length)));
    };
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keyword_id = $form_state->getValue('keyword_id');
    $interest_id = $form_state->getValue('interest_id');
    $message = $form_state->getValue('message');
    $phone = $form_state->getValue('phone');
    $title = $form_state->getValue('title');
    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    if ($phone) {
      $success = $optit->messagePhone($phone, $keyword_id, $title, $message);
    }
    elseif ($interest_id) {
      $success = $optit->messageInterest($interest_id, $title, $message);
    }
    else {
      $success = $optit->messageKeyword($keyword_id, $title, $message);
    }

    if ($success) {
      drupal_set_message($this->t('Message was successfully sent.'));
    }
    else {
      drupal_set_message($this->t('The message could not be sent. Please consult error log for details.', 'error'));
    }
  }

  /**
   * Adds the message to the bulk messages temp store.
   */
  function addToBulk(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();
    $keyword_id = $form_state->getValue('keyword_id');
    $phones = [];
    // If phone number was not set -- message all subscribers to the keyword.
    if (!$form_state->getValue('phone')) {
      $subscriptions = $optit->subscriptionsGet($keyword_id);
      foreach ($subscriptions as $subscription) {
        $phones[] = $subscription->get('phone');
      }
    }
    // Else iterate through submitted values and make a nice flat array
    else {
      // @todo: Wasn't this supposed to be validation's responsibility?!?!
      foreach ($form_state->getValue('phone') as $phone => $selected) {
        if ($selected) {
          $phones[] = $phone;
        }
      }
    }

    $message = [
      'title' => $form_state->getValue('title'),
      'message' => $form_state->getValue('message'),
      'phones' => $phones
    ];

    /** @var \Drupal\user\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('optit_bulk');
    $sms_messages = $tempstore->get('sms_messages');
    if (!isset($sms[$keyword_id])) {
      $sms_messages[$keyword_id] = [];
    }
    $sms_messages[$keyword_id][] = $message;
    $tempstore->set('sms_messages', $sms_messages);

    drupal_set_message($this->t('Message successfully added to the bulk.'));
  }
}

