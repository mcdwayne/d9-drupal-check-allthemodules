<?php

namespace Drupal\normalize_address\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Build Normalize Address settings form.
 */
class NormalizeAddressSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'normalize_address_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['normalize_address.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('normalize_address.settings');

    $form['normalize_address_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Your Google API Key"),
      '#default_value' => $config->get('normalize_address_api_key'),
      '#description' => $this->t("Enter your Google API Key."),
      '#required' => TRUE,
    ];

    $form['normalize_address_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country Code'),
      '#default_value' => $config->get('normalize_address_country'),
      '#description' => $this->t("Enter Country Code. For example CA for Canada."),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $this->config('normalize_address.settings')
      ->setData($values)
      ->save();

    drupal_set_message($this->t('Changes saved.'));
  }

}
