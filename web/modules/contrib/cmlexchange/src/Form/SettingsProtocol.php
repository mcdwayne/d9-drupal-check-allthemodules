<?php

namespace Drupal\cmlexchange\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class SettingsProtocol extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmlexchange_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cmlexchange.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cmlexchange.settings');
    $form['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Security settings'),
      '#open' => TRUE,
    ];
    $form['security']['auth'] = [
      '#title' => $this->t('Need authentication'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('auth'),
    ];
    $form['security']['auth-user'] = [
      '#title' => $this->t('Auth username'),
      '#default_value' => $config->get('auth-user'),
      '#type' => 'textfield',
    ];
    $form['security']['auth-pass'] = [
      '#title' => $this->t('Auth password'),
      '#default_value' => $config->get('auth-pass'),
      '#type' => 'textfield',
    ];
    $form['security']['auth-ip'] = [
      '#title' => $this->t('Restrict by IP'),
      '#default_value' => $config->get('auth-ip'),
      '#type' => 'textfield',
    ];
    $form['cml'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form["cml"]['debug'] = [
      '#title' => $this->t('Debug mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('debug'),
    ];

    $form["cml"]['zip'] = [
      '#title' => $this->t('Allow zip compression'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('zip'),
    ];
    $form['cml']['file-limit'] = [
      '#title' => $this->t('Filesize limit'),
      '#default_value' => $config->get('file-limit', 0),
      '#type' => 'textfield',
      '#description' => $this->t('Размер в байтах'),
    ];
    $form['cml']['file-path'] = [
      '#title' => $this->t('Path for imported images'),
      '#default_value' => $config->get('file-path'),
      '#type' => 'textfield',
      '#description' => $this->t('Путь внутри public://, default: `cml`'),
    ];

    $form['pipeline'] = [
      '#type' => 'details',
      '#title' => $this->t('Pipeline'),
    ];
    $form['pipeline']['cmlmigrations'] = [
      '#title' => $this->t('Try CML Migrations'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cmlmigrations'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cmlexchange.settings');
    $config
      ->set('auth', $form_state->getValue('auth'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('auth-user', $form_state->getValue('auth-user'))
      ->set('auth-pass', $form_state->getValue('auth-pass'))
      ->set('auth-ip', $form_state->getValue('auth-ip'))
      ->set('zip', $form_state->getValue('zip'))
      ->set('file-path', $form_state->getValue('file-path'))
      ->set('file-limit', $form_state->getValue('file-limit'))
      ->set('cmlmigrations', $form_state->getValue('cmlmigrations'))
      ->save();
  }

}
