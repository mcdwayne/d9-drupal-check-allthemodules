<?php

namespace Drupal\commerce_equiv_weight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\physical\MeasurementType;

/**
 * Settings form to set the max equivalency weight for orders.
 */
class OrderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_equiv_weight.order_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_equiv_weight_order_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_equiv_weight.order_settings');
    $form['equiv_weight'] = [
      '#type' => 'physical_measurement',
      '#measurement_type' => MeasurementType::WEIGHT,
      '#title' => $this->t('Maximum order equivalency weight'),
      '#default_value' => $config->get('equiv_weight'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('commerce_equiv_weight.order_settings')
      ->set('equiv_weight', $form_state->getValue('equiv_weight'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
