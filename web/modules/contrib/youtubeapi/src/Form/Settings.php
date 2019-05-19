<?php

/**
 * @file
 * Contains \Drupal\demo\Form\DemoForm.
 */

namespace Drupal\youtubeapi\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\youtubeapi\YoutubeAPIService;

class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'youtubeapi_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [YoutubeAPIService::getConfigName()];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(YoutubeAPIService::getConfigName());

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube API Key'),
      '#default_value' => $config->get('apikey'),
      '#description' => $this->t('Your Key generated from Google API Console (https://console.developers.google.com/)'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => "Save Settings",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getValue('apikey')) {
      $form_state->setErrorByName('apikey', "API key is empty");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(YoutubeAPIService::getConfigName())
      ->set('apikey', $form_state->getValue('apikey'))
      ->set('def_status_publish', $form_state->getValue('def_status_publish'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
