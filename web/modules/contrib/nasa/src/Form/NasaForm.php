<?php

namespace Drupal\nasa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NasaForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nasa_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('nasa.settings');

    // Page title field.
    $form['nasa_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('NASA API key:'),
      '#default_value' => $config->get('nasa.nasa_api_key'),
      '#description' => $this->t('Your own NASA API key'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nasa.settings');
    $config->set('nasa.nasa_api_key', $form_state->getValue('nasa_api_key'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nasa.settings',
    ];
  }

}