<?php

namespace Drupal\database_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Database Backup Configuration Form.
 */
class ConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'database_management_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('database_management.settings');

    $form['s3_region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 Region'),
      '#description' => $this->t('The region your S3 bucket is hosted in.'),
      '#default_value' => $config->get('s3_region') ?? 'us-east-1',
    ];

    $form['s3_bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 Bucket'),
      '#description' => $this->t('The S3 Bucket where your database backups are stored.'),
      '#default_value' => $config->get('s3_bucket'),
      '#required' => TRUE,
    ];

    $form['s3_folder_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 Folder Prefix'),
      '#description' => $this->t('The folder prefix for your backups.'),
      '#default_value' => $config->get('s3_folder_prefix'),
    ];

    $form['s3_file_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 File Prefix'),
      '#description' => $this->t('The file prefix for your backups.'),
      '#default_value' => $config->get('s3_file_prefix'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('database_management.settings');

    $config->set('s3_region', $form_state->getValue('s3_region'));
    $config->set('s3_bucket', $form_state->getValue('s3_bucket'));
    $config->set('s3_folder_prefix', $form_state->getValue('s3_folder_prefix'));
    $config->set('s3_file_prefix', $form_state->getValue('s3_file_prefix'));

    // Save the configuration.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return the configuration names.
   */
  protected function getEditableConfigNames() {
    return [
      'database_management.settings',
    ];
  }

}
