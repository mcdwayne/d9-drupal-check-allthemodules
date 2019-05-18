<?php

namespace Drupal\cloudinary_sdk\Form;

use Cloudinary\Api;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudinary_sdk\CloudinarySdkConstantsInterface;

/**
 * Class CloudinarySdkSettingsForm.
 *
 * @package Drupal\cloudinary_sdk\Form
 */
class CloudinarySdkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudinary_sdk_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloudinary_sdk.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cloudinary_sdk.settings');
    // Make sure Cloudinary SDK installed.
    // If not, display messages and disable API settings.
    list($status, $version, $error_message) = cloudinary_sdk_check(TRUE);
    $disabled = FALSE;

    if ($status == CloudinarySdkConstantsInterface::CLOUDINARY_SDK_NOT_LOADED) {
      drupal_set_message($this->t('Please make sure the Cloudinary SDK library is installed in the libraries directory.'), 'error');
      if ($error_message) {
        drupal_set_message($error_message, 'error');
      }
      return;
    }
    elseif ($status == CloudinarySdkConstantsInterface::CLOUDINARY_SDK_OLD_VERSION) {
      drupal_set_message($this->t('Please make sure the Cloudinary SDK library installed is @version or greater. Current version is @current_version.', [
        '@version' => CloudinarySdkConstantsInterface::CLOUDINARY_SDK_MINIMUM_VERSION,
        '@current_version' => $version,
      ]), 'warning');
      return;
    }

    // Build API settings form.
    $form = [];

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('API Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t('You should enable "Auto-create folders" on cloudinary account. In order to check the validity of the API, system will be auto ping your Cloudinary account after change API settings.'),
    ];

    $form['settings']['cloudinary_sdk_cloud_name'] = [
      '#type' => 'textfield',
      '#title' => t('Cloud name'),
      '#required' => TRUE,
      '#default_value' => $config->get('cloudinary_sdk_cloud_name'),
      '#description' => t('Cloud name of Cloudinary.'),
      '#disabled' => $disabled,
    ];

    $form['settings']['cloudinary_sdk_api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('cloudinary_sdk_api_key'),
      '#description' => t('API key of Cloudinary.'),
      '#disabled' => $disabled,
    ];

    $form['settings']['cloudinary_sdk_api_secret'] = [
      '#type' => 'textfield',
      '#title' => t('API secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('cloudinary_sdk_api_secret'),
      '#description' => t('API secret of Cloudinary.'),
      '#disabled' => $disabled,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cloudinary_sdk.settings');
    $cloud_name = trim($form_state->getValue(['cloudinary_sdk_cloud_name']));
    $api_key = trim($form_state->getValue(['cloudinary_sdk_api_key']));
    $api_secret = trim($form_state->getValue(['cloudinary_sdk_api_secret']));

    // Validate the API settings with ping.
    if ($cloud_name && $api_key && $api_secret) {
      $key = $cloud_name . $api_key . $api_secret;
      $old_key = $config->get('cloudinary_sdk_cloud_name');
      $old_key .= $config->get('cloudinary_sdk_api_key');
      $old_key .= $config->get('cloudinary_sdk_api_secret');

      // Return if no changes.
      if ($key == $old_key) {
        return;
      }

      $config = [
        'cloud_name' => $cloud_name,
        'api_key' => $api_key,
        'api_secret' => $api_secret,
      ];

      // Init cloudinary sdk with new API settings.
      cloudinary_sdk_init($config);

      try {
        $api = new Api();
        $api->ping();
      }
      catch (\Exception $e) {
        $this->logger('cloudinary_sdk')->error($e->getMessage());
        $form_state->setErrorByName('', $e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cloudinary_sdk.settings');
    $values = $form_state->getValues();
    foreach ($values as $field => $value) {
      if (!in_array($field, [
        'op',
        'submit',
        'form_id',
        'form_token',
        'form_build_id',
      ])) {
        $config->set(str_replace('.', '_', $field), $value);
      }
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
