<?php

namespace Drupal\simple_gtm\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class SimpleGtmSettingsForm extends ConfigFormBase {


  /**
   * Constructor for AppearanceSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'simple_gtm_settings_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['simple_gtm.settings'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('simple_gtm.settings');

    $form['color_settings_wrapper']['gtm_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GTM code'),
      '#default_value' => $config->get('gtm_code'),
      '#description' => $this->t('Enther your Google Tag Manager code.'),
      '#attributes' => [
        'placeholder' => $this->t('GTM-XXXXXXX'),
      ],
    ];

    return parent::buildForm($form, $form_state);
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

    $config = $this->config('simple_gtm.settings');
    $values = $form_state->getValues();
    // Saving field values in configuration.
    $config->set('gtm_code', $values['gtm_code'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
