<?php

namespace Drupal\flashpoint\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CourseModuleSettingsForm.
 *
 * @ingroup flashpoint
 */
class FlashpointSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'flashpoint_settings';
  }

  /**
   * @return array
   */
  protected function getEditableConfigNames() {
    return ['flashpoint.settings'];
  }

  /**
   * Generate the settings form.
   *
   * @param string $option_type
   * May be 'form' for settings form elements, or 'key' for keys to use when settings the config.
   *
   * @return mixed
   */
  public static function getSettingsOptions($option_type = 'form') {
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_settings');
    $plugin_definitions = $plugin_manager->getDefinitions();

    $options = [];
    foreach ($plugin_definitions as $pd) {
      switch ($option_type) {
        case 'form':
          $options[$pd['id']] = $pd['class']::getFormOptions();
          break;
      }
    }
    return $options;
  }

  /**
   * Defines the settings form for Course module entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = FlashpointSettingsForm::getSettingsOptions('form');
    $config = $this->config('flashpoint.settings');

    $form['flashpoint'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach($options as $key => $item) {
      foreach ($item as $form_key => $form_item) {
        $form[$form_key] = $form_item;
        $form[$form_key]['#group'] = 'flashpoint';
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit'
    ];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('flashpoint.settings');
    // Clear old data
    $old_data = $config->getRawData();
    foreach ($old_data as $key => $value) {
      $config->clear($key);
    }

    // Set the new data
    $settings = array_diff(array_keys($form_state->getValues()), ['submit', 'form_build_id', 'form_token', 'form_id', 'op']);
    foreach ($settings as $key) {
      $form_val = $form_state->getValue($key);
      $k = str_replace('__', '.', $key);
      $config->set($k, $form_val);
      $config->save();
    }
    drupal_set_message('The configuration options have been saved');
  }

}