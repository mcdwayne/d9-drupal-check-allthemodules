<?php

/**
 *  * @file
 *  * Contains \Drupal\mailjet\Form\MailjetSettingsForm.
 *   */

namespace Drupal\mailjet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use MailJet\MailJet;
use MailjetTools\MailjetApi;
use Mailjet\Resources;

class MailjetApiForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailjet_api.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailjet_api_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config_mailjet = $this->config('mailjet.settings');
    $form = parent::buildForm($form, $form_state);
    $form['api'] = [
      '#type' => 'fieldset',
    ];

    $form['api']['welcome'] = [
      '#markup' => t('Welcome to the Mailjet Configuration page.</br>
      If you are new to Mailjet, please register by clicking on the button above.</br>
      Should you already have a pre-existing Mailjet account, please copy and paste your Mailjet API Key and Secret Key which can be found in <a href="https://app.mailjet.com/account/api_keys">your Mailjet account.</a>'),
    ];

    $form['api']['mailjet_username'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $config_mailjet->get('mailjet_username'),
      '#required' => TRUE,
    ];

    $form['api']['mailjet_password'] = [
      '#type' => 'textfield',
      '#title' => t('Secret Key'),
      '#default_value' => $config_mailjet->get('mailjet_password'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
      $config = \Drupal::service('config.factory')
          ->getEditable('mailjet.settings');

      $config->set('mailjet_username', $form_state->getValue('mailjet_username'));
      $config->set('mailjet_password', $form_state->getValue('mailjet_password'));
      $config->save();

      $mailjetApiClient = MailjetApi::getApiClient($form_state->getValue('mailjet_username'),
      $form_state->getValue('mailjet_password'));
      $response = $mailjetApiClient->get(Resources::$Myprofile);
      if ($response->success()) {
          $config->set('mailjet_active', TRUE);

          $params = [
              'AllowedAccess' => 'campaigns,contacts,stats,pricing,account,reports',
              'APIKeyALT' => $form_state->getValue('mailjet_username'),
              'TokenType' => 'iframe',
              'IsActive' => TRUE,
          ];

          $responseApiToken = MailjetApi::createApiToken($params);
          if (false != $responseApiToken) {
              $config->set('APItoken', $responseApiToken[0]['Token']);
              $config->save();
              mailjet_first_sync(mailjet_get_default_list_id(mailjet_new()));

              drupal_set_message(t('The configuration options have been saved.'));
              drupal_flush_all_caches();
          } else {
              $form_state->setErrorByName('mailjet_username', t('Token was NOT generated! Please try again.'));
          }
      } else {
          drupal_set_message(t('Please verify that you have entered your API and secret key correctly. Please note this plug-in is compatible for Mailjet v3 accounts only. Click <a href=" https://app.mailjet.com/support/why-do-i-get-an-api-error-when-trying-to-activate-a-mailjet-plug-in,497.htm"> here</a> for more information'),
              'error');
      }
  }

}
