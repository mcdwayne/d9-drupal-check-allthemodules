<?php

/**
 * Commerce repeat order settings form.
 */
namespace Drupal\commerce_repeat_order\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CommerceRepeatOrderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_repeat_order.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_repeat_order_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_repeat_order.settings');
    $form['add_or_override'] = [
      '#title' => $this->t("Add Product or Override Products"),
      '#description' => $this->t("Add product to existing cart or override existing cart"),
      '#type' => 'radios',
      '#options' => ['add' => $this->t('Add Product'), 'override' => $this->t('Override')],
      '#required' => TRUE,
      '#default_value' => $config->get('add_or_override'),
    ];

    $form['status_message'] = [
      '#title' => $this->t("Show/Hide add to cart status message"),
      '#description' => $this->t("show or hide add to cart set message of product"),
      '#type' => 'radios',
      '#options' => ['show' => $this->t('Show'), 'hide' => $this->t('Hide')],
      '#required' => TRUE,
      '#default_value' => $config->get('status_message'),
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
    $this->config('commerce_repeat_order.settings')
      ->set('add_or_override', $form_state->getValue('add_or_override'))
      ->set('status_message', $form_state->getValue('status_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
