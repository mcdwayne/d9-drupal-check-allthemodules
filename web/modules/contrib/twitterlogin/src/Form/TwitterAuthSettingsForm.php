<?php

namespace Drupal\twitterlogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Twitter.
 */
class TwitterAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_login_twitter_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twitterlogin.settings',
    ];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitterlogin.settings');

    $form['twitter_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter OAuth API Settings'),
      '#open' => TRUE,
    ];

    $form['twitter_settings']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Key (API Key)'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_key'),
      '#description' => $this->t('Enter Consumer Key from Twitter Application Management'),
    ];

    $form['twitter_settings']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Secret (Secret Key)'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_secret'),
      '#description' => $this->t('Enter Consumer Secret from Twitter Application Management'),
    ];

    $form['twitter_settings']['redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('redirect_url'),
      '#description' => $this->t('Enter Redirect URL'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit Common Admin Settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('twitterlogin.settings')
      ->set('consumer_key', $values['consumer_key'])
      ->set('consumer_secret', $values['consumer_secret'])
      ->set('redirect_url', $values['redirect_url'])
      ->save();

    drupal_set_message($this->t('Settings are updated'));
  }

}
