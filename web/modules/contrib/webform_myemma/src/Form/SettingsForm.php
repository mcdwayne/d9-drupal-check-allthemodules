<?php

namespace Drupal\webform_myemma\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for MyEmma webform field.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_myemma_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_myemma.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_myemma.settings');

    $form_state->setCached(FALSE);

    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MyEmma Account ID'),
      '#default_value' => $config->get('account_id'),
      '#required' => TRUE,
    ];

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MyEmma public key'),
      '#default_value' => $config->get('public_key'),
      '#required' => TRUE,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MyEmma private key'),
      '#default_value' => $config->get('private_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('webform_myemma.settings');

    $config->set('account_id', $form_state->getValue('account_id'))->save();
    $config->set('public_key', $form_state->getValue('public_key'))->save();
    $config->set('private_key', $form_state->getValue('private_key'))->save();

    parent::submitForm($form, $form_state);
  }

}
