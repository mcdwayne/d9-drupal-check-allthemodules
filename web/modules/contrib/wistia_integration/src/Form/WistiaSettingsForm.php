<?php

namespace Drupal\wistia_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WistiaSettingsForm.
 */
class WistiaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wistia_integration.wistiasettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wistia_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wistia_integration.wistiasettings');
    $form['wistia_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wistia Token'),
      '#description' => $this->t('Get wistia token from your wistia account.'),
      '#maxlength' => 512,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('wistia_token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('wistia_integration.wistiasettings')
      ->set('wistia_token', $form_state->getValue('wistia_token'))
      ->save();
  }

}
