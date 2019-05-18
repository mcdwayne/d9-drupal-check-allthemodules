<?php

namespace Drupal\endroid_qr_code\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class QRCodeConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'endroid_qr_code_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'endroid_qr_code.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('endroid_qr_code.settings');
    $form['logo_width'] = [
      '#type' => 'range',
      '#title' => $this->t('QR Logo Width'),
      '#default_value' => $config->get('logo_width'),
      '#min' => 25,
      '#max' => 500,
      '#step' => 25,
    ];
    $form['set_size'] = [
      '#type' => 'range',
      '#title' => $this->t('QR Size'),
      '#default_value' => $config->get('set_size'),
      '#min' => 100,
      '#max' => 1000,
      '#step' => 100,
    ];
    $form['set_margin'] = [
      '#type' => 'range',
      '#title' => $this->t('QR Margin'),
      '#default_value' => $config->get('set_margin'),
      '#min' => 0,
      '#max' => 200,
      '#step' => 5,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('endroid_qr_code.settings')
      ->set('logo_width', (int) $values['logo_width'])
      ->set('set_size', (int) $values['set_size'])
      ->set('set_margin', (int) $values['set_margin'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
