<?php

/**
 * @file
 * Contains \Drupal\swish_payment\Form\SwishSettingsForm
 */
namespace Drupal\swish_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure swish settings for this site.
 */
class SwishSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swish_payment_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'swish_payment.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('swish_payment.settings');
    $form['payee_alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Payee alias'),
      '#description' => $this->t('Enter your Swish number here.'),
      '#default_value' => $config->get('payee_alias'),
      '#required' => true,
    );
    $form['private_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#description' => $this->t('Path and filename to the private key file. Should reside outside if www-root if possible.'),
      '#default_value' => $config->get('private_key'),
      '#required' => true,
    );
    $form['private_key_pw'] = array(
      '#type' => 'password',
      '#title' => $this->t('Private key password'),
      '#description' => $this->t('Password for the private key, if any.'),
      '#default_value' => $config->get('private_key_pw'),
    );
    $form['client_cert'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client cert'),
      '#description' => $this->t('Path and filename to the client cert file. Should reside outside if www-root if possible.'),
      '#default_value' => $config->get('client_cert'),
      '#required' => true,
    );
    $form['client_cert_pw'] = array(
      '#type' => 'password',
      '#title' => $this->t('Client cert password'),
      '#description' => $this->t('Password for the client certificate, if any.'),
      '#default_value' => $config->get('client_cert_pw'),
    );
    $form['ca_cert'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CA root cert'),
      '#description' => $this->t('Path and filename to the CA root cert file. Omit this and you must either import the CA on the server or disable CA verification belove.'),
      '#default_value' => $config->get('ca_cert'),
    );
    $form['disable_ca_verification'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable CA verification'),
      '#description' => $this->t('Check this to disable CA verification.'),
      '#default_value' => $config->get('disable_ca_verification'),
    );
    $form['live_mode'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Live mode'),
      '#description' => $this->t('Check this to call live server.'),
      '#default_value' => $config->get('live_mode'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('swish_payment.settings');
    $config
      ->set('payee_alias', $form_state->getValue('payee_alias'))
      ->set('private_key', $form_state->getValue('private_key'))
      ->set('private_key_pw', $form_state->getValue('private_key_pw'))
      ->set('client_cert', $form_state->getValue('client_cert'))
      ->set('client_cert_pw', $form_state->getValue('client_cert_pw'))
      ->set('ca_cert', $form_state->getValue('ca_cert'))
      ->set('disable_ca_verification', $form_state->getValue('disable_ca_verification'))
      ->set('live_mode', $form_state->getValue('live_mode'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
