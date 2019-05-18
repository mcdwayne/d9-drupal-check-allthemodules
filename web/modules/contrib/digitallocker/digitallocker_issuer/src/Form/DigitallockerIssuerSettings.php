<?php

namespace Drupal\digitallocker_issuer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DigitallockerIssuerSettings.
 *
 * @package Drupal\digitallocker_issuer\Form
 */
class DigitallockerIssuerSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'digitallocker_issuer_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('digitallocker_issuer.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('api_key'),
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Set the API Key provided by Digital Locker.'),
    ];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('base_url'),
      '#title' => $this->t('Base Url'),
      '#description' => $this->t('Set the Base Url provided by Digital Locker.'),
    ];

    $form['issuer_id'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('issuer_id'),
      '#title' => $this->t('Issuer Id'),
      '#description' => $this->t('Set the issuer Id provided by Digital Locker.'),
    ];

    $form['certificate_path'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_path'),
      '#title' => $this->t('Certificate Path'),
      '#description' => $this->t('Path of the certificate file relative to index.php'),
    ];

    $form['certificate_pass'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_pass'),
      '#title' => $this->t('Private Key Password'),
      '#description' => $this->t('Private Key Password of the certificate'),
    ];

    $form['certificate_name'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_name'),
      '#title' => $this->t('Issuer Name'),
      '#description' => $this->t('The Issuer name string to be printed on the pdf'),
    ];

    $form['certificate_reason'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_reason'),
      '#title' => $this->t('Issue Reason'),
      '#description' => $this->t('The Issuer reason string to be printed on the pdf'),
    ];

    $form['certificate_contact'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_contact'),
      '#title' => $this->t('Contact Info'),
      '#description' => $this->t('The Issuer contact info to be printed on the pdf'),
    ];

    $form['certificate_location'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('certificate_location'),
      '#title' => $this->t('Issue Location'),
      '#description' => $this->t('The Issuer location to be printed on the pdf'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    (\Drupal::service('config.factory'))
      ->getEditable('digitallocker_issuer.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('issuer_id', $form_state->getValue('issuer_id'))
      ->set('certificate_path', $form_state->getValue('certificate_path'))
      ->set('certificate_pass', $form_state->getValue('certificate_pass'))
      ->set('certificate_name', $form_state->getValue('certificate_name'))
      ->set('certificate_reason', $form_state->getValue('certificate_reason'))
      ->set('certificate_contact', $form_state->getValue('certificate_contact'))
      ->set('certificate_location', $form_state->getValue('certificate_location'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['digitallocker_issuer.settings'];
  }

}
