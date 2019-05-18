<?php

namespace Drupal\google_hire\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Google Hire configuration settings form.
 */
class GoogleHireSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_hire_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_hire.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add form elements to collect site account information.
    $form['google_hire_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company Domain'),
      '#description' => $this->t('The Google Hire domain to retrieve public listings from.'),
      '#default_value' => $this->config('google_hire.settings')->get('google_hire_domain'),
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_hire.settings')
      ->set('google_hire_domain', $form_state->getValue('google_hire_domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
