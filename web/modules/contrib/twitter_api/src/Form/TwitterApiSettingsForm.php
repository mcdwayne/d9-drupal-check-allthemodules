<?php

namespace Drupal\twitter_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Twitter API Settings.
 */
class TwitterApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twitter_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $twitter_config = $this->configFactory->get('twitter_api.settings');

    $form['oauth_access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('OAuth Token'),
      '#default_value' => $twitter_config->get('oauth_access_token'),
      '#required' => TRUE,
    );
    $form['oauth_access_token_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('OAuth Token secret'),
      '#default_value' => $twitter_config->get('oauth_access_token_secret'),
      '#required' => TRUE,
    );
    $form['consumer_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Consumer key'),
      '#default_value' => $twitter_config->get('consumer_key'),
      '#required' => TRUE,
    );
    $form['consumer_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Consumer secret'),
      '#default_value' => $twitter_config->get('consumer_secret'),
      '#required' => TRUE,
    );
    $form['api_url'] = array(
      '#type' => 'textfield',
      '#title' => t('API Url'),
      '#default_value' => $twitter_config->get('api_url'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $twitter_config = $this->configFactory->getEditable('twitter_api.settings');
    $twitter_config
      ->set('oauth_access_token', $form_state->getValue('oauth_access_token'))
      ->set('oauth_access_token_secret', $form_state->getValue('oauth_access_token_secret'))
      ->set('consumer_key', $form_state->getValue('consumer_key'))
      ->set('consumer_secret', $form_state->getValue('consumer_secret'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
