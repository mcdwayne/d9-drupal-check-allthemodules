<?php

namespace Drupal\friends\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FriendsSettingsForm.
 *
 * @package Drupal\friends\Form
 *
 * @ingroup friends
 */
class FriendsSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'Friends_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('opium_configs.settings')
      // Set the submitted configuration setting.
      ->set('request', $form_state->getValue('request')['value'])
      ->set('accept', $form_state->getValue('accept')['value'])
      ->set('declined', $form_state->getValue('declined')['value'])
      ->save();
  }

  /**
   * Defines the settings form for Friends entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('friends.settings');
    $form['tokens']['#markup'] = $this->t('Tokens are available for the below messages.');
    $form['friends_settings'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['friends'],
      '#dialog' => TRUE,
    ];
    $form['request'] = [
      "#type" => "text_format",
      "#base_type" => "textarea",
      '#required' => TRUE,
      "#rows" => 9,
      "#format" => NULL,
      '#title' => $this->t('Request Notification.'),
      '#default_value' => $config->get('request'),
    ];

    $form['accept'] = [
      "#type" => "text_format",
      "#base_type" => "textarea",
      '#required' => TRUE,
      "#rows" => 9,
      "#format" => NULL,
      '#title' => $this->t('Accepted Friend Request Notification.'),
      '#default_value' => $config->get('accept'),
    ];

    $form['decline'] = [
      "#type" => "text_format",
      "#base_type" => "textarea",
      '#required' => TRUE,
      "#rows" => 9,
      "#format" => NULL,
      '#title' => $this->t('Declined Friend Request Notification.'),
      '#default_value' => $config->get('decline'),
    ];

    return $form;
  }

}
