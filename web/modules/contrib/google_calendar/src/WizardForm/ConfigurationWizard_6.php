<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\google_calendar\GoogleCalendarSecretsException;
use Drupal\google_calendar\GoogleCalendarSecretsFileInterface;
use Drupal\google_calendar\GoogleCalendarSecretsManagedFile;
use Drupal\google_calendar\GoogleCalendarSecretsStaticFile;

class ConfigurationWizard_6 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_6';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $service = \Drupal::service('google_calendar.secrets_file');

    $file_id = NULL;
    $file_content = NULL;
    $secret_type = 'unknown';
    $secret_type_p = t('- unknown type: @class', ['@class' => get_class($service)]);

    if ($service instanceof GoogleCalendarSecretsManagedFile) {
      $secret_type = 'managed';
      $secret_type_p = t('Managed file');
    }
    elseif ($service instanceof GoogleCalendarSecretsStaticFile) {
      $secret_type = 'static';
      $secret_type_p = t('Static file');
    }

    $form['intro'] = array(
      '#type' => 'details',
      '#title' => $this->t('Step 6: Import credentials file'),
      '#open' => TRUE,
    );

    $output = '<p>' . t('The credentials file just downloaded from Google must be made available to the website. There are two supported configuration methods; add others by extending or overriding the Drupal service "google_calendar.secrets_file":') . '</p>';
    $output .= '<ol>';
    $output .= '<li>' . t('Static file: store the file on the server in a location visible to PHP and save that location in a config value.') . '</li>';
    $output .= '<li>' . t('Managed file: upload the file as a Drupal Managed file in the private file area, and store the ID of this managed file as a config value.') . '</li>';
    $output .= '</ol>';
    $output .= '<p>' . t('The secrets_file service is currently set to: @type', ['@type' => $secret_type_p]) . '</p>';
    $output .= '<p>' . t('To change this you will need to update the site service file and then rebuild the cache.') . '</p>';

    $form['intro']['para'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['client_secret_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secrets Storage Settings'),
      '#attributes' => ['class' => ['secrets_configured']]
    ];

    if ($secret_type == 'managed') {
      $private_exists = FALSE;
      try {
        $privatefile_service = \Drupal::service('stream_wrapper.private');
        if ($privatefile_service instanceof StreamWrapperInterface) {
          if ($privatefile_service->realpath('')) {
            $private_exists = TRUE;
          }
        }
      }
      catch (\Exception $ex) {
      }

      $form['client_secret_settings']['client_secret_type'] = [
        '#type' => 'hidden',
        '#value' => 'managed',
      ];

      if (!$private_exists) {
        $form['client_secret_settings']['para'] = [
          '#type' => 'markup',
          '#markup' => t('The Drupal private: filesystem must be configured for managed files to be uploadable.'),
        ];
      }
      else {
        $form['client_secret_settings']['client_secret_managed'] = [
          '#type' => 'managed_file',
          '#title' => t('Upload Client Secret File'),
          '#upload_location' => 'private://google-calendar/',
          '#default_value' => "",
          '#description' => t('Client Secret JSON file.'),
          '#upload_validators' => [
            'file_validate_extensions' => ['json']
          ],
        ];
        $fileid = $service->getFileId();
        if ($fileid !== NULL && $file = File::load($fileid)) {
          $form['client_secret']['#default_value'] = ['target_id' => $file->id()];
        }
      }
    }
    elseif ($secret_type == 'static') {
      $form['client_secret_settings']['client_secret_type'] = [
        '#type' => 'hidden',
        '#value' => 'static',
      ];
      $form['client_secret_settings']['client_secret_static'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client Secret File path'),
        '#default_value' => $service->getFilePath(),
        '#maxlength' => 255,
        '#size' => 50,
        '#description' => t('Server path to the file, either relative to Drupal root or absolute.'),
        '#required' => TRUE,
      ];
    }
    elseif ($service instanceof GoogleCalendarSecretsFileInterface) {
      $form['client_secret_settings']['client_secret_unknown'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The secrets_file service cannot be configured using this wizard.'),
      ];
    }

    $file_content = '';
    try {
      $file_content = $service->get();
    }
    catch (GoogleCalendarSecretsException $ex) {
    }

    $form['client_secret_status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secrets Storage Status'),
      '#attributes' => ['class' => ['secrets_configured']]
    ];
    if (!empty($file_content) && isset($file_content['type'])) {
      $form['client_secret_container']['#attributes']['class'][] = 'success';
      $form['client_secret_container']['client_secret_info'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The secrets have been configured for account: @account.', ['@account' => $file_content['client_email'],]),
      ];
    }
    else {
      $form['client_secret_container']['#attributes']['class'][] = 'failure';
      $form['client_secret_container']['client_secret_info'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The secrets have not been configured (yet).'),
      ];
    }

    $form['step_6'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Google Credentials installed'),
      '#default_value' => $this->store->get('step_6') ?: '',
      '#description' => $this->t('Check this when you have configured the account credentials.')
    ];

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_five'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('secret_type', $form_state->getValue('secret_type'));
    $this->store->set('step_6', $form_state->getValue('step_6'));

    // Save the data
    $this->saveData();
    $form_state->setRedirect('entity.google_calendar.collection');
  }
}