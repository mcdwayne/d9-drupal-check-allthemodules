<?php

namespace Drupal\worldcore\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Worldcore settings form class.
 */
class SettingsForm extends FormBase {

  /**
   * Internal function.
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'worldcore_settings_form';
  }

  /**
   * Worldcore payment form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('worldcore.settings');

    $form['API_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('worldcore_api_key'),
      '#size' => 40,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['API_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $config->get('worldcore_api_password'),
      '#size' => 40,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * Worldcore settings form processing.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()->get('config.factory')->getEditable('worldcore.settings');

    $config->set('worldcore_api_key', $form_state->getValue('API_key'));
    $config->set('worldcore_api_password', $form_state->getValue('API_password'));

    $config->save();

    drupal_set_message($this->t('Settings has been saved.'));

  }

}
