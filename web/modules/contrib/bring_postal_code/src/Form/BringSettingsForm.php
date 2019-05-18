<?php

namespace Drupal\bring_postal_code\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BringSettingsForm.
 */
class BringSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'bring_postal_code.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bring_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bring_postal_code.settings');
    $form['client_url'] = [
      '#title' => $this->t('Client URL'),
      '#type' => 'url',
      '#description' => t('The url used to call Bring with. Include a ? at the end.'),
      '#default_value' => $config->get('client_url'),
    ];
    $form['form_ids'] = [
      '#type' => 'textarea',
      '#title' => t('Attach to forms'),
      '#description' => t('Enter form IDs where the library should be added.'),
      '#default_value' => $config->get('form_ids'),

    ];
    $form['selectors'] = [
      '#type' => 'textarea',
      '#title' => t('Input / output selectors'),
      '#description' => t('Enter input, output and country jQuery selectors, separated by |, one per line. Example: "#code|#name|#country". <br>You can also select a default country below, and skip the country here, like so: "#code|#name"'),
      '#default_value' => $config->get('selectors'),
    ];

    $form['default_country'] = [
      '#type' => 'select',
      '#title' => t('Default country'),
      '#description' => t('The default country to perform lookup on.'),
      '#options' => $this->supportedCountries(),
      '#default_value' => $config->get('default_country'),
      '#required' => TRUE,
    ];

    $form['trigger_length'] = [
      '#type' => 'textfield',
      '#title' => t('Min trigger length'),
      '#description' => t('Minimum amount of character in an input selector before we do a lookup.'),
      '#default_value' => $config->get('trigger_length'),
      '#required' => TRUE,
      '#size' => 6,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Countries supported by Bring.
   *
   * @return array
   *   The array of countries, keyed by country code.
   */
  public static function supportedCountries() {
    return [
      'NO' => t('Norway'),
      'DK' => t('Denmark'),
      'SE' => t('Sweden'),
      'FI' => t('Finland'),
      'NL' => t('Netherlands'),
      'DE' => t('Germany'),
      'US' => t('United States'),
      'BE' => t('Belgium'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bring_postal_code.settings')
      ->set('client_url', $form_state->getValue('client_url'))
      ->set('selectors', $form_state->getValue('selectors'))
      ->set('default_country', $form_state->getValue('default_country'))
      ->set('trigger_length', $form_state->getValue('trigger_length'))
      ->set('form_ids', $form_state->getValue('form_ids'))

      ->save();
  }

}
