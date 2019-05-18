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
 *   id = "pagarme_credit_card",
 *   label = "Pagar.me (Cartão de crédito)",
 *   display_label = "Pagar.me (Cartão de crédito)",
 *    forms = {
 *     "offsite-payment" = "Drupal\pagarme\Plugin\PluginForm\PagarmeCreditCard",
 *   },
 * )
 */
class PagarmeCreditCard extends OffsitePaymentGatewayBase {
  use PagarmeGenericTrait;
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pagarme_server' => '',
      'pagarme_api_key' => '',
      'pagarme_encryption_key' => '',
      'pagarme_async_transaction' => '',
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
      'pagarme_max_installments' => '',
      'pagarme_default_installment' => '',
      'pagarme_interest_rate' => '',
      'pagarme_installment_start_value' => '',
      'pagarme_free_installments' => '',
      'pagarme_card_brands' => '',
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
    $payment_settings['pagarme_max_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of plots'),
      '#description' => $this->t('Maximum accepted number of plots.'),
      '#default_value' => $this->configuration['pagarme_max_installments'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_default_installment'] = [
      '#type' => 'select',
      '#title' => $this->t('Default plot on cart open'),
      '#description' => $this->t('Default plot on cart open.'),
      '#default_value' => $this->configuration['pagarme_default_installment'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_interest_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interest rate to be charged to the transaction'),
      '#description' => $this->t('Interest rate to be charged to the transaction.'),
      '#default_value' => $this->configuration['pagarme_interest_rate'],
      '#size' => 15,
    ];
    //TODO
    // $pagarme_installment_start_value = commerce_currency_amount_to_decimal($config['pagarme_installment_start_value'], $default_currency);
    $payment_settings['pagarme_installment_start_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enable installment above'),
      '#description' => $this->t('Enable the installment for orders with value higher the from informed.'),
      '#default_value' => $this->configuration['pagarme_installment_start_value'],
      '#size' => 15,
    ];
    $payment_settings['pagarme_free_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of plots that do not have interest rate charged'),
      '#description' => $this->t('Number of plots that do not have interest rate charged.'),
      '#default_value' => $this->configuration['pagarme_free_installments'],
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_card_brands'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Flags accepted by Checkout'),
      '#description' => $this->t('Flags accepted by Checkout'),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::cardBrands(),
      '#default_value' => $this->configuration['pagarme_card_brands'],
    ];
    $payment_settings['pagarme_credit_card_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Credit card optional message'),
      '#description' => $this->t('Optional message that will appear below the payment button of credit card payment method.'),
      '#default_value' => $this->configuration['pagarme_credit_card_helper_text'],
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
        $card_brands = $values['advanced_settings']['payment_settings']['pagarme_card_brands'];
        if (!count(array_filter($card_brands))) {
          $form_state->setError($card_brands, $this->t('Credit card company flags accepted on the Checkout. Is required to enable a minimum of one company flag.'));
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
      $this->configuration['pagarme_async_transaction'] = $values['basic_settings']['pagarme_async_transaction'];
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
      $this->configuration['pagarme_payment_methods'] = $values['advanced_settings']['payment_settings']['pagarme_payment_methods'];
      $this->configuration['pagarme_max_installments'] = $values['advanced_settings']['payment_settings']['pagarme_max_installments'];
      $this->configuration['pagarme_default_installment'] = $values['advanced_settings']['payment_settings']['pagarme_default_installment'];
      $this->configuration['pagarme_interest_rate'] = $values['advanced_settings']['payment_settings']['pagarme_interest_rate'];
      $this->configuration['pagarme_installment_start_value'] = $values['advanced_settings']['payment_settings']['pagarme_installment_start_value'];
      $this->configuration['pagarme_free_installments'] = $values['advanced_settings']['payment_settings']['pagarme_free_installments'];
      $this->configuration['pagarme_card_brands'] = $values['advanced_settings']['payment_settings']['pagarme_card_brands'];
      $this->configuration['pagarme_credit_card_helper_text'] = $values['advanced_settings']['payment_settings']['pagarme_credit_card_helper_text'];
    }
  }
}
