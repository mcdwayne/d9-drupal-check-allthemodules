<?php

/**
 * @file
 * Contains \Drupal\droogle_hangout\Form\DroogleHangoutSettingsForm.
 */

namespace Drupal\droogle_hangout\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class DroogleHangoutSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'droogle_hangout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['droogle_hangout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('droogle_hangout.droogle');

    $form['droogle_hangout'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings for Google Console'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['droogle_hangout']['droogle_hangout_master_account_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email address connecting to the Google Console.'),
      '#description' => t('Enter the email address used for your Google Console that is handling the api.'),
      '#default_value' => $config->get('email'),
    );

    $form['droogle_hangout']['droogle_hangout_clientid'] = array(
      '#type' => 'textfield',
      '#title' => t('Google Console Client ID'),
      '#description' => t('Enter the Google Client ID to use.  Visit https://cloud.google.com/console to generate a Client ID.'),
      '#default_value' => $config->get('clientid'),
    );

  $form['droogle_hangout']['droogle_hangout_client_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Google Console Client Secret'),
    '#description' => t('Enter the Google Client Secret to use.  Visit https://cloud.google.com/console to generate a Google Client Secret.'),
    '#default_value' => $config->get('secret'),
  );
  $form['droogle_hangout']['droogle_hangout_refresh_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Google Refresh Token'),
    '#description' => t('Enter the google refresh token created at https://developers.google.com/oauthplayground.'),
    '#default_value' => $config->get('token'),
  );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('droogle_hangout.droogle')
      ->set('email', Html::escape($form_state->getValue('droogle_hangout_master_account_email')))
      ->set('clientid', Html::escape($form_state->getValue('droogle_hangout_clientid')))
      ->set('secret', Html::escape($form_state->getValue('droogle_hangout_client_secret')))
      ->set('token', Html::escape($form_state->getValue('droogle_hangout_refresh_token')))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
