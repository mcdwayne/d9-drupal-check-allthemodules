<?php

namespace Drupal\sentiment_analysis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
/**
 * {@inheritdoc}.
 */
class SentimentAnalysisForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'sentiment.settings'
    ];
  }
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'sentiment_analysis_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('sentiment.settings');
    $form['get_key'] = [
      '#markup' => '<a target="_blank" href="https://www.havenondemand.com/alt/login.html">Click Here to get API key.</a>'
      ];
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => 'API URL',
      '#default_value' => ($config->get('api_url') != NULL) ? $config->get('api_url') : '',
      '#description' => t('API URL for sentiment analysis'),
      '#required' => 'TRUE',
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => 'API Key',
      '#default_value' => ($config->get('api_key') != NULL) ? $config->get('api_key') : '',
      '#description' => t('API key for sentiment analysis'),
      '#required' => 'TRUE',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // To save API key and url.
    $api_url = $form_state->getValue(['api_url']);
    $api_key = $form_state->getValue(['api_key']);
    $this->config('sentiment.settings')
      ->set('api_key', $api_key)
      ->set('api_url', $api_url)
      ->save();
    parent::submitForm($form, $form_state);
    drupal_set_message(t('API key successfully updated'));
  }

}
