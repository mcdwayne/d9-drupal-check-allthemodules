<?php

/**
 * @file
 * Contains \Drupal\typekit_integration\Form\TypekitIntegrationConfigureForm.
 */

namespace Drupal\typekit_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TypekitIntegrationConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'typekit_integration_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('typekit_integration.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['typekit_integration.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['typekit_integration_id'] = [
      '#type' => 'textfield',
      '#title' => t('Kit ID'),
      '#description' => t('Enter your Adobe Typekit Kit ID. You can create a free account at <a href="https://typekit.com">https://typekit.com</a>'),
      '#default_value' => \Drupal::config('typekit_integration.settings')->get('typekit_integration_id'),
      '#size' => 10,
      '#maxlength' => 7,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $api_key = $form_state->getValue(['typekit_integration_id']);
    $pattern = '/^[a-zA-Z0-9-_]+$/';

    if (!preg_match($pattern, $api_key)) {
      $form_state->setErrorByName('typekit_integration_id', t('Typekit ID should contain only letters and numbers'));
    }

    if (\Drupal\Component\Utility\Unicode::strlen($api_key) != 7) {
      $form_state->setErrorByName('typekit_integration_id', t('Typekit ID should be 7 characters long'));
    }
  }

}
?>
