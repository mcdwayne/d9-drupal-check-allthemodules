<?php

namespace Drupal\shipstation_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Shipstation API - Settings form.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ShipstationApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shipstation_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shipstation_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shipstation_api.settings');

    $form['shipstation_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Shipstation Api Settings'),
    ];

    $form['shipstation_settings']['shipstation_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipstation secret'),
      '#default_value' => $config->get('shipstation_secret'),
      '#required' => TRUE,
    ];

    $form['shipstation_settings']['shipstation_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipstation api key'),
      '#default_value' => $config->get('shipstation_api_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shipstation_api.settings')
      ->set('shipstation_secret', $form_state->getValue('shipstation_secret'))
      ->set('shipstation_api_key', $form_state->getValue('shipstation_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
