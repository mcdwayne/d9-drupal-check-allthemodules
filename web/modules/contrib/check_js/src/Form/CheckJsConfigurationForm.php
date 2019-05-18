<?php

/**
 * @file
 * Contains Drupal\check_js\Form\CheckJsConfigurationForm.
 */

namespace Drupal\check_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\xai\Form
 */
class CheckJsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'check_js.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Get message value from variable table.
    $config = $this->config('check_js.settings');
    $check_js_config = $config->get('check_js_message');
    // Set default message value.
    $check_js_content = isset($check_js_config['value']) ? $check_js_config['value'] : $this->check_js_default_message();
    // Set default message format.
    $check_js_format = isset($check_js_config['format']) ? $check_js_config['format'] : 'basic_html';
    
    // Form fieldset component.
    $form['check_js'] = array(
        '#title' => $this->t('Check JS Configuration'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    // Form Textarea filed where display message will be set.
    $form['check_js']['check_js_message'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Message to be displayed'),
      '#description' => $this->t('Enter text that needs to be displayed if javascript is disabled or not supported in browser.'),
      '#default_value' => $check_js_content,
      '#format' => $check_js_format,
    );
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

    $this->config('check_js.settings')
      ->set('check_js_message', $form_state->getValue('check_js_message'))
      ->save();
  }
  
  /**
   * Default text to show if JS is disabled.
   */
  public function check_js_default_message() {
    $default_msg = t('This Site makes heavy use of JavaScript and to work properly, this page requires JavaScript to be enabled.');
    $default_msg .= ' ' . t('Either you have JavaScript disabled or your browser does not support JavaScript.');
    $default_msg .= ' ' . t('If you can enable it in your browser preferences, you may have a better experience.');
    return $default_msg;
  }
}