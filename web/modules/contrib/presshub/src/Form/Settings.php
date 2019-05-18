<?php

namespace Drupal\presshub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Presshub settings form.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'presshub_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['presshub.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('presshub.settings');
    $form['api_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description'   => $this->t('Please login to your Presshub account and generate an API key for this site.'),
      '#required'      => TRUE,
    ];
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    ];
    $form['advanced']['api_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API Endpoint'),
      '#default_value' => !empty($config->get('api_endpoint')) ? $config->get('api_endpoint') : 'https://api.presshub.io/v1',
      '#description'   => $this->t('API endpoint. If empty defatuls to https://api.presshub.io/v1. If you have premium account you would specify your own API endpoint.'),
    ];
    $form['advanced']['timeout'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Timeout'),
      '#default_value' => !empty($config->get('timeout')) ? $config->get('timeout') : 400,
      '#description'   => $this->t('Set maximum time the request is allowed to take. Defaults to 400.'),
    ];
    $form['advanced']['connect_timeout'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Connect timeout'),
      '#default_value' => !empty($config->get('connect_timeout')) ? $config->get('connect_timeout') : 0,
      '#description'   => $this->t('Timeout for the connect phase. Defaults to 0.'),
    ];
    $form['advanced']['amp_signature'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('AMP Signature'),
      '#default_value' => !empty($config->get('amp_signature')) ? $config->get('amp_signature') : uniqid('sig_'),
      '#description'   => $this->t('AMP response signature. This signautre ensures that you are the one who requested AMP content.'),
      '#required'      => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('presshub.settings');
    $config
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->set('connect_timeout', $form_state->getValue('connect_timeout'))
      ->set('api_endpoint', $form_state->getValue('api_endpoint'))
      ->set('amp_signature', $form_state->getValue('amp_signature'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
