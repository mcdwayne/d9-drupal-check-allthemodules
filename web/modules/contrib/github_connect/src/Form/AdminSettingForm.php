<?php

namespace Drupal\github_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Administration page callbacks for the GitHub connect module.
 */

/**
 * Class AdminSettingForm.
 *
 * @package Drupal\github_connect\Form
 */
class AdminSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'github_connect_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'github_connect.settings',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('github_connect.settings');

    $form['github_connect_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Github settings'),
      '#description' => $this->t('Fill in the form below. You will first have to create an application at https://github.com/account/applications/new. Main URL should be set to your domain name and Callback URL to your domain name /github/register/create (http://example.com/github/register/create). After saving the application you will be given the Client ID and Client secret.'),
      '#open' => TRUE,
    );

    $form['github_connect_settings']['client_id'] = array(
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_id'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
    );

    $form['github_connect_settings']['client_secret'] = array(
      '#title' => $this->t('Client secret'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_secret'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('github_connect.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    // Set values in variables.
    parent::submitForm($form, $form_state);
  }

}
