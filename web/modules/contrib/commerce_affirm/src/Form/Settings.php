<?php

namespace Drupal\commerce_affirm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Affirm settings form.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_affirm.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_affirm_settings';
  }

  /**
   * Provides default settings.
   */
  protected function defaultSettings() {
    return [
      'analytics' => FALSE,
      'monthly_payment_on_add_to_cart' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('commerce_affirm.settings');
    $defaults = $this->defaultSettings();
    $form['analytics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Enable Affirm's enhanced analytics support"),
      '#description' => $this->t('This will add a JavaScript snippet to all pages allowing Affirm to track conversion rates based on user interactions on your site.'),
      '#default_value' => $config->get('analytics') ? $config->get('analytics') : $defaults['analytics'],
    ];
    $form['monthly_payment_on_add_to_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Add promotional messaging to Add to Cart forms on product displays rendered in the 'full' view mode."),
      '#default_value' => $config->get('monthly_payment_on_add_to_cart') ? $config->get('monthly_payment_on_add_to_cart') : $defaults['monthly_payment_on_add_to_cart'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $this->config('commerce_affirm.settings')->setData($values)->save();
    parent::submitForm($form, $form_state);
  }

}
