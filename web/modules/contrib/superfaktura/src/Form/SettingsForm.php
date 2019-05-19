<?php

namespace Drupal\superfaktura\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure SuperFaktura settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'superfaktura_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['superfaktura.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('superfaktura.settings');
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SFAPI email'),
      '#default_value' => $config->get('email'),
      '#description' => $this->t("Enter the e-mail address you use to log in Superfaktura."),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SFAPI key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t("Enter the API key. You can obtain yours in your Superfaktura account, in Tools -> API."),
      '#required' => TRUE,
    ];

    $form['company_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company ID'),
      '#default_value' => $config->get('company_id'),
      '#description' => $this->t("Enter Company ID with which you are working through the API (if you have more comapnies)."),
    ];

    $form['invoice_name_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invoice name prefix'),
      '#default_value' => $config->get('invoice_name_prefix'),
      '#description' => $this->t("This text will be displayed as invoice name and superseded by inovice number."),
      '#required' => TRUE,
    ];

    $form['constant'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invoice constant'),
      '#default_value' => $config->get('constant'),
      '#description' => $this->t("Enter the constant identification for the issued invoices."),
      '#required' => TRUE,
    ];

    $form['specific'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invoice specific'),
      '#default_value' => $config->get('specific'),
      '#description' => $this->t("Enter the specific identification for the issued invoices."),
      '#required' => FALSE,
    ];

    $form['maturity'] = [
      '#type' => 'select',
      '#title' => $this->t('Invoice maturity'),
      '#default_value' => $config->get('maturity'),
      '#options' => [
        3 => '3 days',
        7 => '7 days',
        14 => '14 days',
        30 => '30 days',
        90 => '90 days',
      ],
      '#description' => $this->t("Enter the specific identification for the issued invoices."),
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('superfaktura.settings')
      ->set('email', $form_state->getValue('email'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('company_id', $form_state->getValue('company_id'))
      ->set('invoice_name_prefix', $form_state->getValue('invoice_name_prefix'))
      ->set('constant', $form_state->getValue('constant'))
      ->set('specific', $form_state->getValue('specific'))
      ->set('maturity', $form_state->getValue('maturity'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
