<?php

namespace Drupal\behance_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * @file
 * Contains \Drupal\behance_block\Form\BehanceSettingsForm.
 */

/**
 * Defines a form that configures Behance Block settings.
 */
class BehanceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'behance_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['behance_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('behance_block.settings');

    // API key field.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#required' => TRUE,
      '#description' => $this->t('Enter your API key. If you don\'t have one, visit <a target="_blank" href="https://www.behance.net/dev/register">this page</a> and get your Behance API key.'),
      '#default_value' => $config->get('api_key'),
    ];

    // User ID or username field.
    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username or User ID'),
      '#required' => TRUE,
      '#description' => $this->t("Enter the username or user ID of the project's owner."),
      '#default_value' => $config->get('user_id'),
    ];

    // New tab checkbox.
    $form['new_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open links in new tab'),
      '#description' => $this->t('Check this if you want Behance links to be opened in a new tab.'),
      '#default_value' => $config->get('new_tab'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');
    $user_id = $form_state->getValue('user_id');

    $is_data_valid = $this->isDataValid($api_key, $user_id);

    if ($is_data_valid == 403) {
      $form_state->setErrorByName('api_key', $this->t('Your API key is not valid.'));
    }
    elseif ($is_data_valid == 404) {
      $form_state->setErrorByName('user_id', $this->t('User not found.'));
    }
    elseif ($is_data_valid === NULL) {
      $form_state->setErrorByName('api_key', $this->t('Unknown error.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('behance_block.settings');
    $config->set('api_key', $values['api_key']);
    $config->set('user_id', $values['user_id']);
    $config->set('new_tab', $values['new_tab']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Check if API key and User ID are valid.
   */
  private function isDataValid($api_key, $user_id) {
    $client = new Client();

    try {
      $response = $client->get('https://api.behance.net/v2/users/' . $user_id . '?client_id=' . $api_key);
      $response_code = $response->getStatusCode();
    } catch (ClientException $e) {
      $response = $e->getResponse();
      $response_code = $response->getStatusCode();
      watchdog_exception('behance_block', $e);
    }

    // Valid (200 = OK).
    if ($response_code == 200) {
      return 200;
    }
    // API key in not valid.
    elseif ($response_code == 403) {
      return 403;
    }
    // User not found.
    elseif ($response_code == 404) {
      return 404;
    }
    // Unknown error.
    else {
      return NULL;
    }
  }

}
