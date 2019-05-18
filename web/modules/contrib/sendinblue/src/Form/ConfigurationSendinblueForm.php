<?php

namespace Drupal\sendinblue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sendinblue\SendinblueMailin;
use Drupal\sendinblue\SendinblueManager;

/**
 * Parameter Form to login SendinBlue.
 */
class ConfigurationSendinblueForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'sendinblue_form_login';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => t('API Key'),
      ],
      '#size' => 50,
      '#maxlenght' => 100,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Login'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $access_key = $form_state->getValue('access_key');
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS);

    $mailin = new SendinblueMailin(SendinblueManager::API_URL, $access_key);
    $response = $mailin->getAccount();
    if (is_array($response) && ($response['code'] == 'success')) {
      $account_data = $response['data'];
      $count = count($account_data);
      $account_email = $account_data[$count - 1]['email'];
      $account_user_name = $account_data[$count - 1]['first_name'] . ' ' . $account_data[$count - 1]['last_name'];
      $config->set(SendinblueManager::ACCESS_KEY, $access_key)->save();
      $config->set(SendinblueManager::ACCOUNT_EMAIL, $account_email)->save();
      $config->set(SendinblueManager::ACCOUNT_USERNAME, $account_user_name)
        ->save();
      $config->set(SendinblueManager::ACCOUNT_DATA, $account_data)->save();
      $smtp_details = SendinblueManager::updateSmtpDetails();

      if (($smtp_details == FALSE) || ($smtp_details['relay'] == FALSE)) {
        $config->set('sendinblue_on', 0)->save();
      }
      else {
        $config->set('sendinblue_on', 1)->save();
      }

      $mailin->partnerDrupal();
    }
    else {
      if (!empty($access_key)) {
        $form_state->setErrorByName('access_key', t('API key is invalid'));
      }
    }

    // Clear cache for menu tasks.
    drupal_flush_all_caches();
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['sendinblue_form_login.settings'];
  }

}
