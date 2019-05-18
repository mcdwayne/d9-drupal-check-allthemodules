<?php

namespace Drupal\pagarme\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pagarme\Pagarme\Traits\PagarmeGenericTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Pagar.me Checkout Modal payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "pagarme_modal",
 *   label = "Pagar.me (Modal)",
 *   display_label = "Pagar.me (modal)",
 *    forms = {
 *     "offsite-payment" = "Drupal\pagarme\Plugin\PluginForm\PagarmeModal",
 *   },
 * )
 */
class PagarmeModal extends OffsitePaymentGatewayBase {
  use PagarmeGenericTrait;
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pagarme_server' => '',
      'pagarme_api_key' => '',
      'pagarme_encryption_key' => '',
      'pagarme_ui_color' => '#1a6ee1',
      'pagarme_async_transaction' => '',
      'pagarme_display_title' => '',
      'pagarme_display_title_pay_button' => '',
      'pagarme_debug' => '',
      'pagarme_checkout_complete_url' => 'checkout/[commerce_order:order_id]/complete',
      'pagarme_checkout_failure_url' => 'checkout/[commerce_order:order_id]/payment',
      'pagarme_street_number_token' => '',
      'pagarme_phone_ddd_token' => '',
      'pagarme_phone_number_token' => '',
      'pagarme_name_token' => '',
      'pagarme_birthday_token' => '',
      'pagarme_cpf_token' => '',
      'pagarme_cnpj_token' => '',
      'pagarme_birthday_token' => '',
      'pagarme_gender_token' => '',
      'pagarme_customer_data' => '',
      'pagarme_disable_zero_document_number' => '',
      'pagarme_payment_methods' => array('credit_card'),
      'pagarme_max_installments' => '',
      'pagarme_default_installment' => '',
      'pagarme_interest_rate' => '',
      'pagarme_installment_start_value' => '',
      'pagarme_free_installments' => '',
      'pagarme_card_brands' => '',
      'pagarme_boleto_discount' => '',
      'pagarme_boleto_discount_amount' => '',
      'pagarme_boleto_discount_percentage' => '',
      'pagarme_boleto_discount_start' => '',
      'pagarme_boleto_helper_text' => '',
      'pagarme_credit_card_helper_text' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $moduleHandler = \Drupal::service('module_handler');

    $form['basic_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic configurations'),
    ];
    $form['basic_settings']['pagarme_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Pagar.me server'),
      '#options' => [
        'test' => $this->t('TEST'),
        'live' => $this->t('LIVE'),
      ],
      '#description' => $this->t('TEST - Use it to test, LIVE - Use it to process real transactions'),
      '#default_value' => $this->configuration['pagarme_server'],
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('API Key (available on your dashboard)'),
      '#default_value' => $this->configuration['pagarme_api_key'],
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_encryption_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Crypt Key'),
      '#description' => $this->t('Crypt key (available on your dashboard)'),
      '#default_value' => $this->configuration['pagarme_encryption_key'],
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_display_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title displayed on payment method'),
      '#description' => t('Text used on payment page as description for payment method.'),
      '#default_value' => $this->configuration['pagarme_display_title'],
    ];
    $form['basic_settings']['pagarme_display_title_pay_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title displayed on payment button'),
      '#description' => $this->t('Text used on payment button (Payment page).'),
      '#default_value' => $this->configuration['pagarme_display_title_pay_button'],
    ];
    $form['basic_settings']['pagarme_ui_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary color of Checkout user interface'),
      '#default_value' => $this->configuration['pagarme_ui_color'],
      '#description' => $this->t('Primary color of Checkout user interface'),
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_async_transaction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Asynchronous transaction processing'),
      '#description' => $this->t('Leave this option unchecked if you want to use POSTbacks and keep synchronous processing of a transaction.'),
      '#default_value' => $this->configuration['pagarme_async_transaction'],
    ];
    $form['basic_settings']['pagarme_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug for each action of this module'),
      '#default_value' => $this->configuration['pagarme_debug'],
    ];
    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => TRUE
    ];
    
    $form['advanced_settings']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General options'),
      '#open' => TRUE
    ];
    $general_settings = &$form['advanced_settings']['general'];
    $general_settings['pagarme_checkout_complete_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL after complete an transaction'),
      '#description' => $this->t('Redirect URL after complete an transaction (complete checkout).'),
      '#default_value' => $this->configuration['pagarme_checkout_complete_url'],
      '#required' => TRUE,
    ];
    $general_settings['pagarme_checkout_failure_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL of an transaction that can not be processed by Pagar.me'),
      '#description' => $this->t('Redirect URL of an transaction that can not be processed by Pagar.me'),
      '#default_value' => $this->configuration['pagarme_checkout_failure_url'],
      '#required' => TRUE,
    ];
    if ($moduleHandler->moduleExists('token')){
      $general_settings['tokens'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array('commerce_order'),
      );
    }

    $form['advanced_settings']['order_display'] = [
      '#type' => 'details',
      '#title' => $this->t('Order settings'),
    ];
    if ($moduleHandler->moduleExists('token')) {
    $order_display_settings = &$form['advanced_settings']['order_display'];
      $order_display_settings['pagarme_street_number_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Residence address token'),
        '#description' => $this->t('From where you want to get the "residence number" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_street_number_token'],
      ];
      $order_display_settings['pagarme_phone_ddd_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Area code of the phone'),
        '#description' => $this->t('From where you want to get "Area code of the phone" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_phone_ddd_token'],
      ];
      $order_display_settings['pagarme_phone_number_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone number token'),
        '#description' => $this->t('From where you want to get "Phone" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_phone_number_token'],
      ];
      $order_display_settings['pagarme_cpf_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CPF Token'),
        '#description' => $this->t('From where you want to get "CPF" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_cpf_token'],
      ];
      $order_display_settings['pagarme_cnpj_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CNPJ Token'),
        '#description' => $this->t('From where you want to get "CNPJ" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_cnpj_token'],
      ];
      $order_display_settings['pagarme_birthday_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Birthday token'),
        '#description' => $this->t('From where you want to get "Birthday" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_birthday_token'],
      ];
      $order_display_settings['pagarme_gender_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Gender Token'),
        '#description' => $this->t('From where you want to get "Gender" field to sent on Pagar.me. <strong>You can use tokens</strong> here(see token browser below).'),
        '#default_value' => $this->configuration['pagarme_gender_token'],
      ];
      $order_display_settings['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['commerce_order'],
      ];
    }
    
    $form['advanced_settings']['payment_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Payment configurations'),
    ];
    $payment_settings = &$form['advanced_settings']['payment_settings'];
    $payment_settings['pagarme_customer_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Catch customer data on the Checkout?'),
      '#description' => $this->t('In case you do not want to catch customer data on the Checkout, disable this option'),
      '#default_value' => $this->configuration['pagarme_customer_data'],
    ];
    $payment_settings['pagarme_disable_zero_document_number'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dot not accept if either CPF or CNPJ are zeros?'),
      '#default_value' => $this->configuration['pagarme_disable_zero_document_number'],
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_customer_data]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_payment_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Payment methods available on the Checkout'),
      '#description' => $this->t('Payment methods available on the Checkout.'),
      '#options' => ['credit_card' => $this->t('credit card'), 'boleto' => $this->t('billet')],
      '#default_value' => $this->configuration['pagarme_payment_methods'],
    ];
    $payment_settings['pagarme_max_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of plots'),
      '#description' => $this->t('Maximum accepted number of plots.'),
      '#default_value' => $this->configuration['pagarme_max_installments'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_default_installment'] = [
      '#type' => 'select',
      '#title' => $this->t('Default plot on cart open'),
      '#description' => $this->t('Default plot on cart open.'),
      '#default_value' => $this->configuration['pagarme_default_installment'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_interest_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interest rate to be charged to the transaction'),
      '#description' => $this->t('Interest rate to be charged to the transaction.'),
      '#default_value' => $this->configuration['pagarme_interest_rate'],
      '#size' => 15,
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    //TODO
    // $pagarme_installment_start_value = commerce_currency_amount_to_decimal($config['pagarme_installment_start_value'], $default_currency);
    $payment_settings['pagarme_installment_start_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enable installment above'),
      '#description' => $this->t('Enable the installment for orders with value higher the from informed.'),
      '#default_value' => $this->configuration['pagarme_installment_start_value'],
      '#size' => 15,
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_free_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of plots that do not have interest rate charged'),
      '#description' => $this->t('Number of plots that do not have interest rate charged.'),
      '#default_value' => $this->configuration['pagarme_free_installments'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_card_brands'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Flags accepted by Checkout'),
      '#description' => $this->t('Flags accepted by Checkout'),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::cardBrands(),
      '#default_value' => $this->configuration['pagarme_card_brands'],
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];

    $payment_settings['pagarme_boleto_discount'] = [
      '#type' => 'select',
      '#title' => $this->t('Discount on billet'),
      '#description' => $this->t('Select the type of discount for the billet'),
      '#options' => ['_none' => $this->t('None'), 'amount' => $this->t('In cents'), 'percentage' => $this->t('In percent')],
      '#default_value' => $this->configuration['pagarme_boleto_discount'],
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE]],
      ],
    ];
    //TODO
    // $pagarme_boleto_discount_amount = commerce_currency_amount_to_decimal($config['pagarme_boleto_discount_amount'], $default_currency);
    $payment_settings['pagarme_boleto_discount_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discount amount'),
      '#description' => $this->t('Value of the discount if the means of payment is a ticket. Ex: discount of R$ 10.25 = 10.25.'),
      '#default_value' => $this->configuration['pagarme_boleto_discount_amount'],
      '#prefix' => '<div id="boleto-discount-amount-options-replace">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          'input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE],
          'select[name="configuration[advanced_settings][payment_settings][pagarme_boleto_discount]"]' => ['value' => 'amount'],
        ],
      ],
    ];
    $payment_settings['pagarme_boleto_discount_percentage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discount amount'),
      '#description' => $this->t('Discount percent if the payment method be billet. Example: discount of 25% = 25.'),
      '#default_value' => $this->configuration['pagarme_boleto_discount_percentage'],
      '#element_validate' => ['element_validate_integer_positive'],
      '#prefix' => '<div id="boleto-discount-percentage-options-replace">',
      '#suffix' => '</div>',
      '#maxlength' => 2,
      '#size' => 4,
      '#states' => [
        'visible' => [
          'input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE],
          'select[name="configuration[advanced_settings][payment_settings][pagarme_boleto_discount]"]' => ['value' => 'percentage'],
        ],
      ],
    ];
    //TODO
    // $pagarme_boleto_discount_start = commerce_currency_amount_to_decimal($config['pagarme_boleto_discount_start'], $default_currency);
    $payment_settings['pagarme_boleto_discount_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Apply discount from'),
      '#description' => $this->t('Discount will be applied only in orders that the amount is higher than the informed amount. Example: Apply discount from R$999,25  = 999.25.'),
      '#default_value' => $this->configuration['pagarme_boleto_discount_start'],
      '#prefix' => '<div id="boleto-discount-amount-options-replace">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          [
            'input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE],
            'select[name="configuration[advanced_settings][payment_settings][pagarme_boleto_discount]"]' => ['value' => 'amount'],
          ],
          'xor',
          [
            'input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE],
            'select[name="configuration[advanced_settings][payment_settings][pagarme_boleto_discount]"]' => ['value' => 'percentage'],
          ],
        ],
      ],
    ];
    $payment_settings['pagarme_boleto_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional message for billet'),
      '#description' => $this->t('Optional message that will appear below the payment button of billet method.'),
      '#default_value' => $this->configuration['pagarme_boleto_helper_text'],
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][boleto]"]' => ['checked' => TRUE]],
      ],
    ];
    $payment_settings['pagarme_credit_card_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Credit card optional message'),
      '#description' => $this->t('Optional message that will appear below the payment button of credit card payment method.'),
      '#default_value' => $this->configuration['pagarme_credit_card_helper_text'],
      '#states' => [
        'visible' => ['input[name="configuration[advanced_settings][payment_settings][pagarme_payment_methods][credit_card]"]' => ['checked' => TRUE]],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $user_input = $form_state->getUserInput()['configuration'];
    if (!empty($user_input['basic_settings']['pagarme_api_key']) && !empty($user_input['basic_settings']['pagarme_encryption_key']) ) {
        if (!$form_state->getErrors()) {
        $values = $form_state->getValue($form['#parents']);
        $payment_methods = $values['advanced_settings']['payment_settings']['pagarme_payment_methods'];
        if (!count(array_filter($payment_methods))) {
          $form_state->setError($payment_methods, $this->t('Available payment methods on the checkout. Is required to enable a minimum of one payment method.'));
        }
        if (in_array('credit_card', $values['advanced_settings']['payment_settings']['pagarme_payment_methods'])) {
          $card_brands = $values['advanced_settings']['payment_settings']['pagarme_card_brands'];
          if (!count(array_filter($card_brands))) {
            $form_state->setError($card_brands, $this->t('Credit card company flags accepted on the Checkout. Is required to enable a minimum of one company flag.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['pagarme_server'] = $values['basic_settings']['pagarme_server'];
      $this->configuration['pagarme_api_key'] = $values['basic_settings']['pagarme_api_key'];
      $this->configuration['pagarme_encryption_key'] = $values['basic_settings']['pagarme_encryption_key'];
      $this->configuration['pagarme_ui_color'] = $values['basic_settings']['pagarme_ui_color'];
      $this->configuration['pagarme_async_transaction'] = $values['basic_settings']['pagarme_async_transaction'];
      $this->configuration['pagarme_display_title'] = $values['basic_settings']['pagarme_display_title'];
      $this->configuration['pagarme_display_title_pay_button'] = $values['basic_settings']['pagarme_display_title_pay_button'];
      $this->configuration['pagarme_debug'] = $values['basic_settings']['pagarme_debug'];
      $this->configuration['pagarme_checkout_complete_url'] = $values['advanced_settings']['general']['pagarme_checkout_complete_url'];
      $this->configuration['pagarme_checkout_failure_url'] = $values['advanced_settings']['general']['pagarme_checkout_failure_url'];
      $this->configuration['pagarme_street_number_token'] = $values['advanced_settings']['order_display']['pagarme_street_number_token'];
      $this->configuration['pagarme_phone_ddd_token'] = $values['advanced_settings']['order_display']['pagarme_phone_ddd_token'];
      $this->configuration['pagarme_phone_number_token'] = $values['advanced_settings']['order_display']['pagarme_phone_number_token'];
      $this->configuration['pagarme_name_token'] = $values['advanced_settings']['order_display']['pagarme_name_token'];
      $this->configuration['pagarme_birthday_token'] = $values['advanced_settings']['order_display']['pagarme_birthday_token'];
      $this->configuration['pagarme_cpf_token'] = $values['advanced_settings']['order_display']['pagarme_cpf_token'];
      $this->configuration['pagarme_cnpj_token'] = $values['advanced_settings']['order_display']['pagarme_cnpj_token'];
      $this->configuration['pagarme_birthday_token'] = $values['advanced_settings']['order_display']['pagarme_birthday_token'];
      $this->configuration['pagarme_gender_token'] = $values['advanced_settings']['order_display']['pagarme_gender_token'];
      $this->configuration['pagarme_customer_data'] = $values['advanced_settings']['payment_settings']['pagarme_customer_data'];
      $this->configuration['pagarme_disable_zero_document_number'] = $values['advanced_settings']['payment_settings']['pagarme_disable_zero_document_number'];
      $this->configuration['pagarme_payment_methods'] = $values['advanced_settings']['payment_settings']['pagarme_payment_methods'];
      $this->configuration['pagarme_max_installments'] = $values['advanced_settings']['payment_settings']['pagarme_max_installments'];
      $this->configuration['pagarme_default_installment'] = $values['advanced_settings']['payment_settings']['pagarme_default_installment'];
      $this->configuration['pagarme_interest_rate'] = $values['advanced_settings']['payment_settings']['pagarme_interest_rate'];
      $this->configuration['pagarme_installment_start_value'] = $values['advanced_settings']['payment_settings']['pagarme_installment_start_value'];
      $this->configuration['pagarme_free_installments'] = $values['advanced_settings']['payment_settings']['pagarme_free_installments'];
      $this->configuration['pagarme_card_brands'] = $values['advanced_settings']['payment_settings']['pagarme_card_brands'];
      $this->configuration['pagarme_boleto_discount'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount'];
      $this->configuration['pagarme_boleto_discount_amount'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_amount'];
      $this->configuration['pagarme_boleto_discount_percentage'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_percentage'];
      $this->configuration['pagarme_boleto_discount_start'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_start'];
      $this->configuration['pagarme_boleto_helper_text'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_helper_text'];
      $this->configuration['pagarme_credit_card_helper_text'] = $values['advanced_settings']['payment_settings']['pagarme_credit_card_helper_text'];
    }
  }
}
