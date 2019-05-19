<?php

namespace Drupal\social_auth_amazon\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Amazon.
 */
class AmazonAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_amazon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_amazon.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_amazon.settings');

    $form['amazon_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Amazon Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create an Amazon app at <a href="@amazon-dev">@amazon-dev</a> by signing in and clicking on "Register New Application"',
        ['@amazon-dev' => 'https://login.amazon.com/manageApps']),
    ];

    $form['amazon_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['amazon_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['amazon_settings']['allowed_return_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Allowed Return URL'),
      '#description' => $this->t('Copy this value to <em>Allowed Return URLs</em> field of your Amazon App settings.'),
      '#default_value' => Url::fromRoute('social_auth_amazon.callback')->setAbsolute()->toString(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Convert the string of space-separated scopes into an array.
    $scopes = explode(" ", $form_state->getValue('scopes'));

    // Define the list of valid scopes.
    $valid_scopes = ['', 'profile', 'profile:user_id', 'postal_code'];

    // Check if input contains any invalid scopes.
    for ($i = 0; $i < count($scopes); $i++) {
      if (!in_array($scopes[$i], $valid_scopes, TRUE)) {
        $contains_invalid_scope = TRUE;
      }
    }
    if (isset($contains_invalid_scope)) {
      $form_state->setErrorByName('scope', t('You have entered an invalid scope. Please check and try again.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_amazon.settings')
      ->set('client_id', trim($values['client_id']))
      ->set('client_secret', trim($values['client_secret']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
