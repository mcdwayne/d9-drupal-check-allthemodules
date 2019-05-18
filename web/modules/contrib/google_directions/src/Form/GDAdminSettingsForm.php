<?php

namespace Drupal\google_directions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides configuration settings.
 */
class GDAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'GDAdminSettingsForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_directions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_directions.settings');
    if (empty($config->get('google_api_key'))) {
      $default_google_api_key = "";
    }
    else {
      $default_google_api_key = $config->get('google_api_key');
    }
    $form['google_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#required' => TRUE,
      '#default_value' => $default_google_api_key,
      '#description' => $this->t('To get a key, visit <a href="@google-api-url">https://developers.google.com/maps/documentation/directions/</a> and follow the "Quick start steps".', ['@google-api-url' => 'https://developers.google.com/maps/documentation/directions/']),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_directions.settings')->set('google_api_key', $form_state->getValue('google_api_key'))->save();
    parent::submitForm($form, $form_state);
  }

}
