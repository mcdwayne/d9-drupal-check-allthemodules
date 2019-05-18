<?php

namespace Drupal\nasa_apod\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NasaApodConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nasa_apod_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('nasa_apod.settings');

    $form['nasa_apod_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Astronomy Picture of the Day API URL'),
      '#default_value' => empty($config->get('url')) ? 'https://api.nasa.gov/planetary/apod' : $config->get('url'),
    ];

    $form['nasa_apod_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('key'),
    ];

    $form['nasa_apod_hi_res'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Retrieve high resolution images when available'),
      '#default_value' => empty($config->get('hi_res')) ? FALSE : $config->get('hi_res'),
    ];

    return $form;
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
    $config = $this->config('nasa_apod.settings');
    $config->set('url', $form_state->getValue('nasa_apod_url'));
    $config->set('key', $form_state->getValue('nasa_apod_api_key'));
    $config->set('hi_res', $form_state->getValue('nasa_apod_hi_res'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {$inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nasa_apod.settings',
    ];
  }

}
