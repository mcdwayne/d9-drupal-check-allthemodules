<?php

namespace Drupal\breezy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Breezy configuration settings form.
 */
class BreezySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breezy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['breezy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add form elements to collect site account information.
    $form['breezy_company_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company ID'),
      '#description' => $this->t('A Breezy company id.'),
      '#default_value' => $this->config('breezy.settings')->get('breezy_company_id'),
      '#required' => TRUE,
    ];

    $form['account'] = [
      '#type' => 'details',
      '#title' => $this->t('Account Settings'),
      '#description' => $this->t('The Breezy API <a href=":url">requires a login and password to retrieve an access token</a> which is valid for 30 days. It is reccomended that you create a Breezy login specifically for this API use.', [':url' => 'https://developer.breezy.hr/docs/signin']),
      '#open' => TRUE,
    ];

    $form['account']['breezy_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Breezy Account Email'),
      '#default_value' => $this->config('breezy.settings')->get('breezy_email'),
      '#required' => TRUE,
    ];

    $form['account']['breezy_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Breezy Account Password'),
      '#default_value' => $this->config('breezy.settings')->get('breezy_password'),
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('breezy.settings')
      ->set('breezy_company_id', $form_state->getValue('breezy_company_id'))
      ->set('breezy_email', $form_state->getValue('breezy_email'))
      ->set('breezy_password', $form_state->getValue('breezy_password'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
