<?php

/**
 * @file
 * Contains \Drupal\salsa_api\Form\SalsaSettingsForm.
 */

namespace Drupal\salsa_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\salsa_api\SalsaApiInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure site information settings for this site.
 */
class SalsaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['salsa_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salsa_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('salsa_api.settings');
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL to Salsa API'),
      '#description' => $this->t('Type the URL to the Salsa API that your organizations node is on. ex: https://hq-org2.democracyinaction.org'),
      '#default_value' => $config->get('url'),
      '#size' => 50,
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Campaign Manager Username'),
      '#description' => $this->t('Type the username of the Campaign Manager that you are using to login to the Salsa interface.'),
      '#default_value' => $config->get('username'),
      '#size' => 50,
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Campaign Manager Password'),
      '#description' => $this->t('Type the password of the Campaign Manager that you entered above.'),
      '#default_value' => $config->get('password'),
      '#size' => 50,
    );
    $form['query_timeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('Query Timeout'),
      '#description' => $this->t('Number of seconds before a Salsa API query times out'),
      '#default_value' => $config->get('query_timeout'),
      '#size' => 4,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $salsa_api = \Drupal::service('salsa_api');
    $response = $salsa_api->testConnect($form_state->getValue('url'), $form_state->getValue('username'), $form_state->getValue('password'));
    switch ($response) {
      case SalsaApiInterface::CONNECTION_AUTHENTICATION_FAILED:
        // Login failed, incorrect password and/or username.
        $form_state->setErrorByName('password', $this->t('Username and/or password incorrect.'));
        break;

      case SalsaApiInterface::CONNECTION_WRONG_URL:
        // 404 page / server down / any other error.
        $form_state->setErrorByName('url', $this->t('This page is not available, please type in a correct URL or try again later.'));
        break;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('salsa_api.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('query_timeout', $form_state->getValue('query_timeout'))
      ->save();

    drupal_set_message(t('The connection has been successfully tested.'));
    parent::submitForm($form, $form_state);
  }

}
