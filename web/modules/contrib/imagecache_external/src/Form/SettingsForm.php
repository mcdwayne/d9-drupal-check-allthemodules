<?php

namespace Drupal\imagecache_external\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures uploadcare settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imagecache_external_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'imagecache_external.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('imagecache_external.settings');

    $form['imagecache_directory'] = [
      '#type' => 'textfield',
      '#title' => t('Imagecache Directory'),
      '#required' => TRUE,
      '#description' => t('Where, within the files directory, should the downloaded images be stored?'),
      '#default_value' => $config->get('imagecache_directory'),
      '#validate' => '::validateForm',
    ];

    $form['imagecache_default_extension'] = [
      '#type' => 'select',
      '#options' => [
        '' => 'none',
        '.jpg' => 'jpg',
        '.png' => 'png',
        '.gif' => 'gif',
        '.jpeg' => 'jpeg',
      ],
      '#title' => t('Imagecache default extension'),
      '#required' => FALSE,
      '#description' => t('If no extension is provided by the external host, specify a default extension'),
      '#default_value' => $config->get('imagecache_default_extension'),
    ];

    $form['imagecache_external_management'] = [
      '#type' => 'radios',
      '#title' => t('How should Drupal handle the files?'),
      '#description' => t('Managed files can be re-used elsewhere on the site, for instance in the Media Library if you use the Media module. Unmanaged files are not saved to the database, but can be cached using Image Styles.'),
      '#options' => [
        'unmanaged' => t('Unmanaged: Only save the images to the files folder to be able to cache them. This is  default.'),
        'managed' => t('Managed: Download the images and save its metadata to the database.'),
      ],
      '#default_value' => $config->get('imagecache_external_management'),
    ];

    $form['imagecache_external_use_whitelist'] = [
      '#type' => 'checkbox',
      '#title' => t('Use whitelist'),
      '#description' => t('By default, all images are blocked except for images served from white-listed hosts. You can define hosts below.'),
      '#default_value' => $config->get('imagecache_external_use_whitelist'),
    ];

    $form['imagecache_external_hosts'] = [
      '#type' => 'textarea',
      '#title' => t('Imagecache External hosts'),
      '#description' => t('Add one host per line. You can use top-level domains to whitelist subdomains. Ex: staticflickr.com to whitelist farm1.staticflickr.com and farm2.staticflickr.com'),
      '#default_value' => $config->get('imagecache_external_hosts'),
      '#states' => [
        'visible' => [
          ':input[name="imagecache_external_use_whitelist"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['imagecache_fallback_image'] = [
      '#type' => 'managed_file',
      '#name' => 'imagecache_fallback_image',
      '#title' => t('Fallback image'),
      '#description' => t("When an external image couldn't be found, use this image as a fallback."),
      '#default_value' => $config->get('imagecache_fallback_image'),
      '#upload_location' => 'public://',
    ];

    $form['#validate'][] = '::validateForm';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $scheme = file_default_scheme();
    $directory = $scheme . '://' . $form_state->getValue('imagecache_directory');
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      $error = $this->t('The directory %directory does not exist or is not writable.', ['%directory' => $directory]);
      $form_state->setErrorByName('imagecache_directory', $error);
      $this->logger('imagecache_external')->error($error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('imagecache_external.settings')
      ->set('imagecache_directory', $values['imagecache_directory'])
      ->set('imagecache_default_extension', $values['imagecache_default_extension'])
      ->set('imagecache_external_management', $values['imagecache_external_management'])
      ->set('imagecache_external_use_whitelist', $values['imagecache_external_use_whitelist'])
      ->set('imagecache_external_hosts', $values['imagecache_external_hosts'])
      ->set('imagecache_fallback_image', $values['imagecache_fallback_image'])
      ->save();
  }

}
