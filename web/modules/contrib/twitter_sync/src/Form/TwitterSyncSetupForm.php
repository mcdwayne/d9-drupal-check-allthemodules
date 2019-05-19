<?php

namespace Drupal\twitter_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with Twitter's connection data.
 */
class TwitterSyncSetupForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twitter_sync_settup_form.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter-sync-settup-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitter_sync_settup_form.settings');

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You must first create an App at <a href=":app-twitter" target="_blank">Twitter App</a>. Check out <a href=":video-tutorial" target="_blank">this video</a> tutorial for detailed instruction.', [
        ':app-twitter' => 'https://apps.twitter.com',
        ':video-tutorial' => 'https://www.youtube.com/watch?v=3dsvNw5AiLc',
      ]),
    ];

    $form['field_twitter_sync_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Key'),
      '#description' => $this->t('Your API key, under <em>Keys and tokens / Consumer API keys</em>'),
      '#required' => TRUE,
      '#default_value' => $config->get('field_twitter_sync_consumer_key'),
    ];
    $form['field_twitter_sync_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer secret'),
      '#description' => $this->t('Your API secret key, under <em>Keys and tokens / Consumer API keys</em>'),
      '#required' => TRUE,
      '#default_value' => $config->get('field_twitter_sync_consumer_secret'),
    ];
    $form['field_twitter_sync_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#description' => $this->t('Your Consumer Key, under <em>Keys and tokens / Access tokens & access token secret</em>'),
      '#required' => TRUE,
      '#default_value' => $config->get('field_twitter_sync_access_token'),
    ];
    $form['field_twitter_sync_access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token secret'),
      '#description' => $this->t('Your Consumer Key, under <em>Keys and tokens / Access tokens & access token secret</em>'),
      '#required' => TRUE,
      '#default_value' => $config->get('field_twitter_sync_access_token_secret'),
    ];

    // Screen name.
    $form['field_twitter_screen_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Screen Name'),
      '#description' => $this->t('Your Twitter screen name with the @ sign'),
      '#required' => TRUE,
      '#default_value' => $config->get('field_twitter_screen_name'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('twitter_sync_settup_form.settings')
      // Set the submitted configuration setting.
      ->set('field_twitter_sync_consumer_key', $form_state->getValue('field_twitter_sync_consumer_key'))
      ->set('field_twitter_sync_consumer_secret', $form_state->getValue('field_twitter_sync_consumer_secret'))
      ->set('field_twitter_sync_access_token', $form_state->getValue('field_twitter_sync_access_token'))
      ->set('field_twitter_sync_access_token_secret', $form_state->getValue('field_twitter_sync_access_token_secret'))
      ->set('field_twitter_screen_name', $form_state->getValue('field_twitter_screen_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
