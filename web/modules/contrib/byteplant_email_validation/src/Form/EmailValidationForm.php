<?php

namespace Drupal\byteplant_email_validation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EmailValidationForm.
 *
 * @package Drupal\byteplant_email_validation\Form
 */
class EmailValidationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'byteplant_email_validation.byteplant_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'byteplant_key';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('byteplant_email_validation.byteplant_settings');
    $default_url = 'https://api.email-validator.net/api/verify';
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BytePlant key'),
      '#description' => $this->t('Your byte plant key.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('key'),
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BytePlant url'),
      '#description' => $this->t('Your byte plant url.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => ($config->get('url')) ? $config->get('url') : $default_url,
    ];

    $form['forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Forms to validate'),
      '#description' => $this->t('Forms where you want to apply the email validation, please insert form id per line.'),
      '#default_value' => $config->get('forms'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom invalid email message'),
      '#description' => $this->t('A static custom message that will be showed if the email is invalid.
      Leave blank to show  dynamic validation details provided by byteplant.'),
      '#default_value' => $config->get('message'),
    ];

    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('byteplant_email_validation.byteplant_settings')
      ->set('key', $form_state->getValue('key'))
      ->set('url', $form_state->getValue('url'))
      ->set('forms', $form_state->getValue('forms'))
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
