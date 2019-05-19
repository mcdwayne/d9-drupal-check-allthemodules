<?php

namespace Drupal\ymaps_geolocation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class YmapsGeolocationSettingsForm.
 *
 * @package Drupal\ymaps_geolocation\Form
 */
class YmapsGeolocationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymaps_geolocation_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ymaps_geolocation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymaps_geolocation.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Yandex API key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['center'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Widget center map'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['center']['center_lat'] = [
      '#title' => $this->t('Map center latitude'),
      '#type' => 'textfield',
      '#default_value' => $config->get('center_lat'),
    ];

    $form['center']['center_lng'] = [
      '#title' => $this->t('Map center longitude'),
      '#type' => 'textfield',
      '#default_value' => $config->get('center_lng'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the updated settings.
    $this->config('ymaps_geolocation.settings')
      ->set('api_key', $values['api_key'])
      ->set('center_lat', $values['center_lat'])
      ->set('center_lng', $values['center_lng'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
