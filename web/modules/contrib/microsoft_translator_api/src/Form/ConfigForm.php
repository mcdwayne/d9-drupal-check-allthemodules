<?php

namespace Drupal\microsoft_translator_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for the API key.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'microsoft_translator_api_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'microsoft_translator_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('microsoft_translator_api.settings');

    $form['key'] = [
      '#title' => $this->t("Microsoft Translator API's key"),
      '#type' => 'textfield',
      '#description' => $this->t("Enter your Microsoft Translator API's key. See README.txt for your one."),
      '#required' => TRUE,
      '#maxlength' => 100,
      '#default_value' => $config->get('key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    try {
      \Drupal::httpClient()->request('GET', 'https://api.microsofttranslator.com/V2/Http.svc/Detect?text=submmit', [
        'headers' => [
          'Ocp-Apim-Subscription-Key' => $form_state->getValue('key'),
        ],
      ]);
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('key', $this->t('The test request was unsuccessful. Your API key is invalid, or the service is not accessible.'));
      watchdog_exception('microsoft_translator_api', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('microsoft_translator_api.settings')
      ->set('key', $form_state->getValue('key'))
      ->set('other_things', $form_state->getValue('key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
