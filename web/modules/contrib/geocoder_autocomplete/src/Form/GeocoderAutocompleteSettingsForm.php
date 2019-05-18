<?php

namespace Drupal\geocoder_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class GeocoderAutocompleteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geocoder_autocomplete_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['geocoder_autocomplete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('geocoder_autocomplete.settings');

    $form['region_code_bias'] = [
      '#type' => 'textfield',
      '#title' => t('Region code bias'),
      '#default_value' => $config->get('region_code_bias'),
      '#maxlength' => 2,
      '#description' => t('2 letter region code used as a bias for geocoding requests.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('geocoder_autocomplete.settings')
      ->set('region_code_bias', $form_state->getValue('region_code_bias'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
