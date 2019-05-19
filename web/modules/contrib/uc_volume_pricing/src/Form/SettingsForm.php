<?php

namespace Drupal\uc_volume_pricing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Volume price configurations.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'volume_pricing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['show_in_cart'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show possible savings message in cart review.'),
      '#default_value' => $this->config('uc_volume_pricing.settings')->get('show_in_cart'),
    );

    $form['cart_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Savings message.'),
      '#default_value' => $this->config('uc_volume_pricing.settings')->get('cart_message'),
      '#description' => $this->t('Available tokens are: [remaining], [product_title] and [savings]'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('show_in_cart')) {
      if ($form_state->isValueEmpty('cart_message')) {
        $form_state->setErrorByName('cart_message', $this->t('Please provide savings message.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $this->configFactory()->getEditable('uc_volume_pricing.settings')
      ->set('show_in_cart', $form_state->getValue('show_in_cart'))
      ->set('cart_message', $form_state->getValue('cart_message'))
      ->save();
    drupal_set_message(t('Configuration has been updated.'), 'status');
    parent::submitForm($form, $form_state);
  }

}
