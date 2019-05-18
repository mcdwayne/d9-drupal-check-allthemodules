<?php

namespace Drupal\file_processor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FileProcessorAdmin.
 *
 * @package Drupal\file_processor\Form
 */
class FileProcessorAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'file_processor.FileProcessorAdmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_processor_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('file_processor.FileProcessorAdmin');

    $manager = \Drupal::service('plugin.manager.file_processor');
    $plugin_definitions = $manager->getDefinitions();

    if (!file_processor_verify_requirements()) {
      drupal_set_message($this->t('You must configure the binaries first.'), 'warning');
    }

    $form['process_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Files'),
      '#default_value' => $config->get('process_files'),
      '#disabled' => !file_processor_verify_requirements(),
    ];

    $form['batch_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Limit'),
      '#default_value' => $config->get('batch_limit'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="process_files"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $field_options = $this->getFieldOptions($plugin_definitions);

    foreach ($field_options as $machine_name => $options) {
      $human_name = str_replace('_', '/', $machine_name);

      $form[$machine_name] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@extension Files', array('@extension' => $human_name)),
        '#states' => [
          'visible' => [
            'input[name="process_files"]' => array('checked' => TRUE),
          ],
        ],
      ];

      $default_values = $config->get($machine_name);
      $form[$machine_name][$machine_name . '_processor'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Processor'),
        '#options' => $options,
        '#default_value' => $default_values['processor'],
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param $plugin_definitions
   *   Plugin defined on modules.
   * @return array
   *   Array with options.
   */
  public function getFieldOptions($plugin_definitions) {
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_value) {
      $machine_name = str_replace('/', '_', $plugin_value['mime_type']);

      $options[$machine_name][$plugin_id] = render($plugin_value['name']);
    }

    return $options;
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

    $form_config = $this->config('file_processor.FileProcessorAdmin')
      ->set('process_files', $form_state->getValue('process_files'))
      ->set('batch_limit', $form_state->getValue('batch_limit'));

    $manager = \Drupal::service('plugin.manager.file_processor');
    $plugin_definitions = $manager->getDefinitions();
    $configs = array_keys($this->getFieldOptions($plugin_definitions));

    foreach ($configs as $config_name) {
      $group_configs = [];
      $group_configs['processor'] = $form_state->getValue($config_name . '_processor');
      $form_config->set($config_name, $group_configs);
    }

    $form_config->save();
  }

}
