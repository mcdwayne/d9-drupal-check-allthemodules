<?php

namespace Drupal\nocaptcha_recaptcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NocaptchaSettingsForm.
 *
 * @package Drupal\nocaptcha_recaptcha\Form
 */
class NocaptchaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nocaptcha_recaptcha.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nocaptcha_recaptcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nocaptcha_recaptcha.settings');

    $form['nocaptcha_site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NoCaptcha site key'),
      '#description' => $this->t('Register your domain &amp; get an API key from <a href="https://www.google.com/recaptcha/admin">https://www.google.com/recaptcha/admin</a>'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('nocaptcha_site_key'),
    ];

    $form['nocaptcha_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NoCaptcha secret key'),
      '#description' => $this->t('Register your domain &amp; get a secret key from <a href="https://www.google.com/recaptcha/admin">https://www.google.com/recaptcha/admin</a>'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('nocaptcha_secret_key'),
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Select the theme for your nocaptcha recaptcha'),
      '#options' => array('dark' => $this->t('dark'), 'light' => $this->t('light')),
      '#default_value' => $config->get('theme'),
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

    $this->config('nocaptcha_recaptcha.settings')
      ->set('nocaptcha_site_key', $form_state->getValue('nocaptcha_site_key'))
      ->set('theme', $form_state->getValue('theme'))
      ->set('nocaptcha_secret_key', $form_state->getValue('nocaptcha_secret_key'))
      ->save();
  }

}
