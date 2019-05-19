<?php

/**
 * @file
 * Contains \Drupal\timed_messages\Form\TimedMessagesForm.
 */

namespace Drupal\timed_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TimedMessagesForm
 * @package Drupal\timed_messages\Form
 */
class TimedMessagesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'timed_messages_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['timed_messages.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('timed_messages.settings');

    // Test Buttons wrapper.
    $form['test_buttons'] = array(
      '#type' => 'details',
      '#title' => t('Test buttons'),
      '#open' => TRUE,
    );

    // General settings.
    $form['general_settings'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
      '#open' => TRUE,
    );
    $form['general_settings']['message_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Message class'),
      '#description' => t('The CSS class to select a message.'),
      '#default_value' => $settings->get('message_class'),
    );
//    $form['general_settings']['kint'] = array(
//      '#type' => 'checkbox',
//      '#title' => t('Also hide when kint is found in message'),
//      '#description' => t('If not ticked, the message will not be hidden after the timer runs out if a kint message is found.'),
//      '#default_value' => $settings->get('kint'),
//    );

    $form['general_settings']['always_testing'] = array(
      '#type' => 'checkbox',
      '#title' => t('Always show test messages.'),
      '#description' => t('If ticked, some pirate ipsum test messages are always shown. This is useful to test or develop a theme.'),
      '#default_value' => $settings->get('always_testing'),
    );

    // General settings to be reused by test buttons.
    $buttons = array(
      '#type' => 'submit',
      '#submit' => array('::exampleMessages'),
    );

    $message_types = $this->availableMessageTypes();
    foreach ($message_types as $key => $message_type) {

      // Message settings.
      $form[$key . '_messages'] = array(
        '#type' => 'details',
        '#title' => t('@type messages', array('@type' => $message_type['name'])),
        '#open' => TRUE,
      );
      $form[$key . '_messages'][$key . '_hide'] = array(
        '#type' => 'checkbox',
        '#title' => t('Hide @type messages after timeout?', array('@type' => $message_type['name'])),
        '#default_value' => $settings->get($key . '_hide'),
      );
      $form[$key . '_messages'][$key . '_time'] = array(
        '#type' => 'textfield',
        '#title' => t('@type message time', array('@type' => $message_type['name'])),
        '#description' => t('How long should the @type message be shown (in ms).', array('@type' => $message_type['name'])),
        '#default_value' => $settings->get($key . '_time'),
      );
      $form[$key . '_messages'][$key . '_class'] = array(
        '#type' => 'textfield',
        '#title' => t('@type message class', array('@type' => $message_type['name'])),
        '#description' => t('The CSS class to select a @type.', array('@type' => $message_type['name'])),
        '#default_value' => $settings->get($key . '_class'),
      );

      // Test Buttons.
      $form['test_buttons'][$key . '_button'] = array(
        '#value' => t('Test ' . $message_type['name'] . ' message'),
          '#name' => $key,
      ) + $buttons;
    }

    //TODO: since page is redirected kint can't be read. Figure out if it
    // could be done different or if kint functionality should be removed.
//    // Kint test button
//    $form['test_buttons']['kint_button'] = array(
//      '#value' => t('Test Kint message'),
//        '#name' => 'kint',
//    ) + $buttons;
    $form['test_buttons']['multi_button'] = array(
      '#value' => t('Test multiple messages'),
        '#name' => 'multi',
    ) + $buttons;


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('timed_messages.settings')
      ->set('message_class', $form_state->getValue('message_class'))
      ->set('always_testing', $form_state->getValue('always_testing'))
      ->set('kint', $form_state->getValue('kint'))
      ->save();

    $message_types = $this->availableMessageTypes();
    foreach ($message_types as $key => $message_type) {
      $this->config('timed_messages.settings')
        ->set($key . '_hide', $form_state->getValue($key . '_hide'))
        ->set($key . '_time', $form_state->getValue($key . '_time'))
        ->set($key . '_class', $form_state->getValue($key . '_class'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * @return array
   * Provides availableMessageTypes.
   */
  public static function availableMessageTypes() {
    $message_types = array(
      'status' => array(
        'name' => 'Status',
        'time' => 5000,
      ),
      'warning' => array(
        'name' => 'Warning',
        'time' => 10000,
      ),
      'error' => array(
        'name' => 'Error',
        'time' => 15000,
      ),
    );

    return $message_types;
  }

  /**
   * @param $type
   * Testbutton callback.
   */
  public function exampleMessages($form, $form_state) {

    $trigger = $form_state->getTriggeringElement();
//    TODO should be checked if kint is enabled, but module_exists
//     (and d8 version) don't seem to work.
//    if($trigger['#name'] == 'kint'){
//      kint('yip yip');
//    }

    if($trigger['#name'] == 'multi'){
      drupal_set_message('This is a message of type "status".', 'status');
      drupal_set_message('This is a message of type "warning".', 'warning');
      drupal_set_message('This is another message of type "error".', 'status');
      drupal_set_message('This is a message of type "error".', 'error');

    }else {
      drupal_set_message('This is a message of type "' . $trigger['#name'] . '".', $trigger['#name']);
    }

  }
}