<?php

namespace Drupal\file_processor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FileProcessorBinariesAdmin.
 *
 * @package Drupal\file_processor\Form
 */
class FileProcessorBinariesAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'file_processor.FileProcessorBinariesAdmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_processor_binaries_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('file_processor.FileProcessorBinariesAdmin');

    $manager = \Drupal::service('plugin.manager.file_processor');
    $plugin_definitions = $manager->getDefinitions();

    foreach ($plugin_definitions as $key => $plugin_definition) {
      $plugin = new $plugin_definition['class']($plugin_definition, $plugin_definition['id'], $plugin_definition);

      $form[$key] = [
        '#type' => 'textfield',
        '#description' => $this->t('Path of @name binary. By default it uses the binary provide by the module, it\'s compiled to linux x64', array('@name' => $plugin_definition['name'])),
        '#title' => $this->t('@name Path', array('@name' => $plugin_definition['name'])),
        '#required' => TRUE,
        '#default_value' => $plugin->getBinaryPath($config),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::service('plugin.manager.file_processor');
    $plugin_definitions = $manager->getDefinitions();

    foreach ($plugin_definitions as $key => $plugin_definition) {
      $path = $form_state->getValue($key);
      if (!file_exists($path)) {
        $form_state->setError($form[$key], $this->t('The path !path does not exists', array('!path' => $path)));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_config = $this->config('file_processor.FileProcessorBinariesAdmin');

    $manager = \Drupal::service('plugin.manager.file_processor');
    $plugin_definitions = $manager->getDefinitions();

    foreach ($plugin_definitions as $key => $plugin_definition) {
      if (!empty($form_state->getValue($key))) {
        $form_config->set($key, $form_state->getValue($key));
      }
    }

    $form_config->save();
  }

}