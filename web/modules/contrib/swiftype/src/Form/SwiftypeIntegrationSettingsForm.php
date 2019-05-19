<?php

/**
 * @file
 * Contains \Drupal\swiftype_integration\Form\SwiftypeIntegrationSettingsForm.
 */

namespace Drupal\swiftype_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Unicode;

class SwiftypeIntegrationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swiftype_integration_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['swiftype_integration.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['swiftype_integration_install_key'] = [
      '#type' => 'textfield',
      '#title' => t('Swiftype install key'),
      '#description' => t('Enter your Swiftype install key'),
      '#default_value' => \Drupal::config('swiftype_integration.settings')->get('swiftype_integration_install_key'),
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue(['swiftype_integration_install_key']);
    $pattern = '/^[a-zA-Z0-9-_]+$/';

    if (!preg_match($pattern, $api_key)) {
      $form_state->setErrorByName('swiftype_integration_install_key', t('Swiftype install key
    should contain only letters and numbers'));
    }

    if (Unicode::strlen($api_key) != 20) {
      $form_state->setErrorByName('swiftype_integration_install_key', t('Swiftype install key
    should be 20 characters long'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('swiftype_integration.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();
    // Clear render cache when install key is saved to show actual search form
    // instead of "No valid Swiftype install key were entered" message
    entity_render_cache_clear();

    parent::submitForm($form, $form_state);
  }

}
