<?php

namespace Drupal\tealium\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tealium Admin form.
 */
class TealiumAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tealium_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tealium.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('tealium.settings');

    $form['tealium'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Tealium Universal Tag settings'),
      '#collapsible' => FALSE,
    ];

    $form['tealium']['tealium_account'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tealium account'),
      '#default_value' => $settings->get('tealium_account'),
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $form['tealium']['tealium_profile'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tealium profile'),
      '#default_value' => $settings->get('tealium_profile'),
      '#size'          => 20,
      '#required'      => TRUE,
    ];

    $tealium_environment_options = [
      'dev' => $this->t('Development'),
      'qa' => $this->t('Testing / QA'),
      'prod' => $this->t('Production'),
    ];

    $form['tealium']['tealium_environment'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Tealium environment'),
      '#description' => $this->t(
        'Set as either your Development, internal Testing or publicly available Production web-site.'
      ),
      '#default_value' => $settings->get('tealium_environment'),
      '#options'       => $tealium_environment_options,
      '#required'      => TRUE,
    ];

    $form['tealium']['tealium_utag_async'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Asynchronously'),
      '#description' => $this->t(
        'Load the Tealium Universal Tag asynchronously (recommended).',
        [
          '%settings_file' => 'sites/domain.name/settings.php',
          '%variable_name' => '$conf[\'tealium_async\']',
        ]
      ),
      '#default_value' => $settings->get('tealium_utag_async'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tealium.settings')
      ->set('tealium_account', $form_state->getValue('tealium_account'))
      ->set('tealium_profile', $form_state->getValue('tealium_profile'))
      ->set('tealium_environment', $form_state->getValue('tealium_environment'))
      ->set('tealium_utag_async', $form_state->getValue('tealium_utag_async'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
