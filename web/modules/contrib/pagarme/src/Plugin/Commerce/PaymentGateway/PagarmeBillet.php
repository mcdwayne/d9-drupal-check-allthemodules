<?php
namespace Drupal\pagarme\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pagarme\Pagarme\Traits\PagarmeGenericTrait as PagarmeGenericTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Pagar.me Checkout Billet payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "pagarme_billet",
 *   label = "Pagar.me (Boleto)",
 *   display_label = "Pagar.me (Boleto)",
 *    forms = {
 *     "offsite-payment" = "Drupal\pagarme\Plugin\PluginForm\PagarmeBillet",
 *   },
 * )
 */
class PagarmeBillet extends OffsitePaymentGatewayBase {
  use PagarmeGenericTrait;
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pagarme_server' => '',
      'pagarme_api_key' => '',
      'pagarme_encryption_key' => '',
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
      'pagarme_boleto_discount' => '',
      'pagarme_boleto_discount_amount' => '',
      'pagarme_boleto_discount_percentage' => '',
      'pagarme_boleto_discount_start' => '',
      'pagarme_boleto_helper_text' => '',
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
    if ($moduleHandler->moduleExists('token')) {
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
    $payment_settings['pagarme_boleto_discount'] = [
      '#type' => 'select',
      '#title' => $this->t('Discount on billet'),
      '#description' => $this->t('Select the type of discount for the billet'),
      '#options' => ['_none' => $this->t('None'), 'amount' => $this->t('In cents'), 'percentage' => $this->t('In percent')],
      '#default_value' => $this->configuration['pagarme_boleto_discount'],
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
    ];
    $payment_settings['pagarme_boleto_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Optional message for billet'),
      '#description' => $this->t('Optional message that will appear below the payment button of billet method.'),
      '#default_value' => $this->configuration['pagarme_boleto_helper_text'],
    ];
    return $form;
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
      $this->configuration['pagarme_boleto_discount'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount'];
      $this->configuration['pagarme_boleto_discount_amount'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_amount'];
      $this->configuration['pagarme_boleto_discount_percentage'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_percentage'];
      $this->configuration['pagarme_boleto_discount_start'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_discount_start'];
      $this->configuration['pagarme_boleto_helper_text'] = $values['advanced_settings']['payment_settings']['pagarme_boleto_helper_text'];

    }
  }
}
