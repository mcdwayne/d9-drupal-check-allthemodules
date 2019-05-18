<?php

namespace Drupal\commerce_confirm_leave\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_confirm_leave.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_confirm_leave.settings');
    $form['confirmation_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('The message displayed while leaving the order process.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('confirmation_message'),
    ];
    $form['routes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Routes'),
      '#description' => $this->t('Routes that will show up a confirmation message.'),
      '#options' => ['cart_page' => $this->t('Cart page'), 'checkout_form' => $this->t('Checkout form')],
      '#default_value' => $config->get('routes'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_confirm_leave.settings')
      ->set('confirmation_message', $form_state->getValue('confirmation_message'))
      ->set('routes', $form_state->getValue('routes'))
      ->save();
  }

}
