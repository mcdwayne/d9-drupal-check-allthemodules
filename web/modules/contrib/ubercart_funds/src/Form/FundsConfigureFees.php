<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_payment\Entity\PaymentMethod;

/**
 * Form to configure the fees for each transaction type.
 */
class FundsConfigureFees extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_configure_fees';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_funds.settings');

    $enabled_methods = PaymentMethod::loadMultiple() ?: NULL;
    $currency = $this->config('uc_store.settings')->get('currency.code');

    $fees = $config->get('fees') ? $config->get('fees') : [];

    $form['ubercart_funds'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ubercart funds'),
      '#collapsible' => FALSE,
    ];

    $form['ubercart_funds']['transfer_rate'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Transfer Fee (%)'),
      '#description' => $this->t('Commission rate taken on Transfers'),
      '#default_value' => $fees ? $fees['transfer_rate'] : 0,
      '#step' => 0.01,
      '#size' => 2,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['ubercart_funds']['transfer_fixed'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Fixed Transfer Fee (@currency)', ['@currency' => $currency]),
      '#description' => $this->t('Fixed fee taken (or minimum applied if a rate is defined) on Transfers.'),
      '#default_value' => $fees ? $fees['transfer_fixed'] : 0,
      '#step' => 0.01,
      '#size' => 2,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['ubercart_funds']['escrow_rate'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Escrow Fee (%)'),
      '#description' => $this->t('Commission rate taken on Escrows'),
      '#default_value' => $fees ? $fees['escrow_rate'] : 0,
      '#step' => 0.01,
      '#size' => 2,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['ubercart_funds']['escrow_fixed'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Fixed Escrow Fee (@currency)', ['@currency' => $currency]),
      '#description' => $this->t('Fixed fee taken (or minimum applied if a rate is defined) on Escrows.'),
      '#default_value' => $fees ? $fees['escrow_fixed'] : 0,
      '#step' => 0.01,
      '#size' => 2,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['deposit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Deposit Fees'),
      '#collapsible' => FALSE,
    ];

    if ($enabled_methods) {
      $enabled_methods_num = count($enabled_methods);
      foreach ($enabled_methods as $method) {
        $method_id = $method->getPlugin()->getPluginId();

        if ($method_id != 'free_order' && $method_id != 'funds') {
          $form['deposit']['deposit_' . $method->id() . '_rate'] = [
            '#type' => 'number',
            '#min' => 0,
            '#title' => $this->t('@method Fee (%)', ['@method' => $method->label()]),
            '#description' => $this->t('Fee taken for Deposits made using @method', ['@method' => $method->label()]),
            '#default_value' => array_key_exists('deposit_' . $method->id() . '_rate', $fees) ? $fees['deposit_' . $method->id() . '_rate'] : 0,
            '#step' => 0.01,
            '#size' => 2,
            '#maxlength' => 3,
            '#required' => TRUE,
          ];

          $form['deposit']['deposit_' . $method->id() . '_fixed'] = [
            '#type' => 'number',
            '#min' => 0,
            '#title' => $this->t('Fixed @method Fee (@currency)', [
              '@method' => $method->label(),
              '@currency' => $currency,
            ]
            ),
            '#description' => $this->t('Fixed fee taken (or minimum applied if a rate is defined) for Deposits made using @method', ['@method' => $method->label()]),
            '#default_value' => array_key_exists('deposit_' . $method->id() . '_fixed', $fees) ? $fees['deposit_' . $method->id() . '_fixed'] : 0,
            '#step' => 0.01,
            '#size' => 2,
            '#maxlength' => 3,
            '#required' => TRUE,
          ];
        }
        if ($method_id == "free_order" && $enabled_methods_num <= 1) {
          $form['deposit']['no_methods'] = [
            '#markup' => $this->t('No paying deposit method have been enabled.'),
          ];
        }
      }
    }
    else {
      $form['deposit']['no_methods'] = [
        '#markup' => $this->t('No payment method have been enabled for deposit.'),
      ];
    }

    $form['withdraw'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Withdrawal Fees'),
      '#collapsible' => FALSE,
    ];

    $withdrawal_methods = $this->config('uc_funds.settings')->get('withdrawal_methods')['methods'];

    if ($withdrawal_methods && !empty(array_filter($withdrawal_methods))) {
      foreach ($withdrawal_methods as $method_id => $method) {
        if ($method && $method_id != 'balance_account') {
          $form['withdraw']['withdraw_' . $method_id . '_rate'] = [
            '#type' => 'number',
            '#min' => 0,
            '#title' => $this->t('@method Fee (%)', ['@method' => $method]),
            '#description' => $this->t('Fee taken for Withdrawals made using @method', ['@method' => $method]),
            '#default_value' => array_key_exists('withdraw_' . $method_id . '_rate', $fees) ? $fees['withdraw_' . $method_id . '_rate'] : 0,
            '#step' => 0.01,
            '#size' => 2,
            '#maxlength' => 3,
            '#required' => TRUE,
          ];

          $form['withdraw']['withdraw_' . $method_id . '_fixed'] = [
            '#type' => 'number',
            '#min' => 0,
            '#title' => $this->t('Fixed @method Fee (@currency)', [
              '@method' => $method,
              '@currency' => $currency,
            ]
            ),
            '#description' => $this->t('Fixed fee taken (or minimum applied if a rate is defined) for Withdrawals made using @method', ['@method' => $method]),
            '#default_value' => array_key_exists('withdraw_' . $method_id . '_fixed', $fees) ? $fees['withdraw_' . $method_id . '_fixed'] : 0,
            '#step' => 0.01,
            '#size' => 2,
            '#maxlength' => 3,
            '#required' => TRUE,
          ];
        }
      }
    }
    else {
      $form['withdraw']['no_methods'] = [
        '#markup' => $this->t('No withdrawal method have been enabled.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    // Set commission fees in a fee array.
    $this->config('uc_funds.settings')
      ->set('fees', $values)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
