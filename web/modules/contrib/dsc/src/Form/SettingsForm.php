<?php

/**
 * @file
 * Contains \Drupal\system\Form\LoggingForm.
 */

namespace Drupal\dsc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure logging settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsc_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dsc.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dsc.settings');
    $form['default_row_limit'] = array(
      '#type' => 'number',
      '#title' => t('Default number of watchdog entries for each type that is not overriden below.'),
      '#default_value' => $config->get('default_row_limit'),
      '#min' => -1,
    );
    module_load_include('admin.inc', 'dblog');
    $filters = dblog_filters();
    $existing = array();
    $form['details'] = array(
      '#type' => 'details',
      '#title' => t('Detailled settings'),
      '#open' => FALSE,
      '#description' => t('Set the value to -1 to reset and inherit the default value.'),
    );
    foreach ($filters['type']['options'] as $type_name => $type_label) {
      $type_name = str_replace(' ', '_', strtolower($type_name));
      $existing[] = $type_name;
      $form['details']['dsc_' . $type_name] = array(
        '#type' => 'fieldset',
        '#title' => t('Number of watchdog entries of type <em>@type</em> to keep', array('@type' => $type_name)),
        '#attributes' => array('class' => array('container-inline')),
      );
      foreach ($filters['severity']['options'] as $severity) {
        $severity = str_replace(' ', '_', strtolower($severity));
        $default_value = !is_null($config->get('dsc_num_' . $type_name . '_' . $severity)) ? $config->get('dsc_num_' . $type_name . '_' . $severity) : '';
        $form['details']['dsc_' . $type_name]['dsc_num_' . $type_name . '_' . $severity] = array(
          '#type' => 'number',
          '#title' => $severity,
          '#default_value' => $default_value,
          '#min' => -1,
          '#placeholder' => 'inherit',
        );
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dsc.settings');
    $form_state->cleanValues();
    $default = $form_state->getValue('default_row_limit');
    if ($default === -1) {
      $default = 100;
    }
    $config->set('default_row_limit', $default);
    $form_state->unsetValue('default_row_limit');
    foreach ($form_state->getValues() as $setting => $value) {
      if ($value === -1 || !is_numeric($value) || $value === $default) {
        $config->clear($setting);
      }
      else {
        $config->set($setting, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
