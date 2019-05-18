<?php

namespace Drupal\externalauth_gitlab\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('externalauth_gitlab.settings');

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('The client id'),
      '#default_value' => $config->get('client_id'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#description' => $this->t('The client secret'),
      '#default_value' => $config->get('client_secret'),
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    // Display result.
    $config = \Drupal::service('config.factory')->getEditable('externalauth_gitlab.settings');
    $keys = ['client_id', 'client_secret', 'domain'];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

  }

}
