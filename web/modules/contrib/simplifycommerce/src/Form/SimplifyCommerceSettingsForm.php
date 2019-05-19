<?php

namespace Drupal\simplifycommerce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimplifyCommerceSettingsForm.
 *
 * @package Drupal\simplifycommerce\Form
 */
class SimplifyCommerceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simplifycommerce.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplify_commerce_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simplifycommerce.settings');

    $form['api_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('API Mode'),
      '#default_value' => $config->get('api_mode'),
      '#options' => [
        'test' => 'Test',
        'live' => 'Live',
      ],
    ];

    $form['live_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live API Public Key'),
      '#default_value' => $config->get('live_public_key'),
    ];

    $form['live_private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live API Private Key'),
      '#default_value' => $config->get('live_private_key'),
    ];

    $form['test_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test API Public Key'),
      '#default_value' => $config->get('test_public_key'),
    ];

    $form['test_private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test API Private Key'),
      '#default_value' => $config->get('test_private_key'),
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

    $this->config('simplifycommerce.settings')
      ->set('api_mode', $form_state->getValue('api_mode'))
      ->set('live_public_key', $form_state->getValue('live_public_key'))
      ->set('live_private_key', $form_state->getValue('live_private_key'))
      ->set('test_public_key', $form_state->getValue('test_public_key'))
      ->set('test_private_key', $form_state->getValue('test_private_key'))
      ->save();
  }

}
