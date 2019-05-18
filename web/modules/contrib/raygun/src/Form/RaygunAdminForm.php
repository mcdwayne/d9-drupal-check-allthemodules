<?php

/**
 * @file
 * Contains \Drupal\raygun\Form\RaygunAdminForm.
 */

namespace Drupal\raygun\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RaygunAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'raygun_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['raygun.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['common'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Common'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['common']['apikey'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API key'),
      '#description' => $this->t('Raygun.io API key for the application.'),
      '#default_value' => $this->config('raygun.settings')->get('apikey'),
    ];
    $form['common']['async_sending'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use asynchronous sending'),
      '#description' => $this->t('If checked, the message will be sent asynchronously. This provides a great speedup versus the older cURL method. On some environments (e.g. Windows), you might be forced to uncheck this.'),
      '#default_value' => $this->config('raygun.settings')->get('async_sending'),
    ];
    $form['common']['send_version'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send application version'),
      '#description' => $this->t('If checked, all error messages to Raygun.io will include your application version that is currently running. This is optional but recommended as the version number is considered to be first-class data for a message.'),
      '#default_value' => $this->config('raygun.settings')->get('send_version'),
    ];
    $form['common']['application_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application version'),
      '#description' => $this->t('What is the current version of your Drupal application. This can be any string or number or even a git commit hash.'),
      '#default_value' => $this->config('raygun.settings')->get('application_version'),
      '#states' => [
        'invisible' => [
          ':input[name="send_version"]' => [
            'checked' => FALSE
          ]
        ]
      ],
    ];
    $form['common']['send_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send current user email'),
      '#description' => $this->t('If checked, all error messages to Raygun.io will include the current email address of any logged in users.  This is optional - if it is not checked, a random identifier will be used.'),
      '#default_value' => $this->config('raygun.settings')->get('send_email'),
    ];

    $form['php'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('PHP'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['php']['exceptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Register global exception handler'),
      '#default_value' => $this->config('raygun.settings')->get('exceptions'),
    ];
    $form['php']['error_handling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Register global error handler'),
      '#default_value' => $this->config('raygun.settings')->get('error_handling'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Simple API key pattern matching.
    if (!preg_match("/^[0-9a-zA-Z\+\/]{22}==$/", $values['apikey'])) {
      $form_state->setErrorByName('apikey', $this->t('You must specify a valid Raygun.io API key. You can find this on your dashboard.'));
    }

    $application_version = trim($values['application_version']);
    if ($values['send_version'] && empty($application_version)) {
      $form_state->setErrorByName('application_version', $this->t('You must specify an application version if you are going to send this.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('raygun.settings');

    $config->set('apikey', $form_state->getValue('apikey'));
    $config->set('async_sending', $form_state->getValue('async_sending'));
    $config->set('send_version', $form_state->getValue('send_version'));
    $config->set('application_version', $form_state->getValue('application_version'));
    $config->set('send_email', $form_state->getValue('send_email'));
    $config->set('exceptions', $form_state->getValue('exceptions'));
    $config->set('error_handling', $form_state->getValue('error_handling'));
    $config->set('send_username', $form_state->getValue('send_username'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
