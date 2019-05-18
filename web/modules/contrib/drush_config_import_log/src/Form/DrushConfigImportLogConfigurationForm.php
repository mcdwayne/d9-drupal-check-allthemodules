<?php
/**
 * @file
 * Contains Drupal\drush_config_import_log\Form\DrushConfigImportLogConfigurationForm.
 */

namespace Drupal\drush_config_import_log\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Class DrushConfigImportLogConfigurationForm
 *
 * @package Drupal\drush_config_import_log\Form
 */

class DrushConfigImportLogConfigurationForm extends ConfigFormBase{
  /*
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drush_config_import_log.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drush_config_import_log_configuration_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drush_config_import_log.settings');
    $form['drush_location'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Drush Configuration Import Log Location'),
      '#default_value' => $config->get('drush_location'),
      '#description' => $this->t('This form is used to set location for Drush Config Import Log.'),
    ];
    
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('drush_config_import_log.settings')
    ->set('drush_location', $form_state->getValue('drush_location'))
    ->save();
  }
}