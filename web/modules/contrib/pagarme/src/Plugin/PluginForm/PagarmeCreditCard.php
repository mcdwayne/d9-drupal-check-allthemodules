<?php

namespace Drupal\pagarme\Plugin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class PagarmeCreditCard extends PaymentOffsiteForm {
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();
    $plugin_config = $payment_gateway_plugin->getConfiguration();
    $pagarmeDrupal = new \Drupal\pagarme\Pagarme\PagarmeDrupal($plugin_config['pagarme_api_key']);
    $pagarmeDrupal->setPluginConfiguration($plugin_config);
    $pagarmeDrupal->setOrder($order);
    $currency_code = $payment->getAmount()->getCurrencyCode();
    
    $default = [
      'type' => '',
      'number' => '',
      'exp_month' => date('m'),
      'exp_year' => date('Y'),
    ];

    $pagarme_encryption_key = $plugin_config['pagarme_encryption_key'];

    $form['#attached']['library'][] = 'pagarme/pagarme';

    $settings = [
      'pagarme' => [
        'encryption_key' => $pagarme_encryption_key,
      ],
    ];
    $form['#attached']['drupalSettings'] = $settings;

    $card_brands = \Drupal\pagarme\Helpers\PagarmeUtility::cardBrands();
    $form['pagarme_whitelabel'] = [
      '#type' => 'details',
      '#title' => t('Payment with credit card'),
      '#open' => TRUE
    ];
    $form['pagarme_whitelabel']['answer'] = [
      '#attributes' => ['class' => 'pagarme-cp-answer'],
      '#type' => 'hidden',
    ];
    $form['pagarme_whitelabel']['messages'] = [
      '#type' => 'markup',
      '#markup' => '<div id="pagarme-cp-messages"></div>',
    ];
    $form['pagarme_whitelabel']['credit_card']['type'] = [
      '#type' => 'select',
      '#title' => t('Card type'),
      '#options' => array_intersect_key($card_brands, array_filter($plugin_config['pagarme_card_brands'])),
      '#default_value' => $default['type'],
    ];

    $form['pagarme_whitelabel']['credit_card']['number'] = [
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#default_value' => $default['number'],
      '#attributes' => ['autocomplete' => 'off', 'pattern' => '[0-9]*'],
      '#maxlength' => 19,
      '#size' => 20,
    ];

    $form['pagarme_whitelabel']['credit_card']['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name (as written in the card)'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $cc_expiration_container = &$form['pagarme_whitelabel']['credit_card'];
    $cc_expiration_container['month_expiration'] = [
      '#type' => 'select',
      '#title' => t('Expiration'),
      '#options' => PagarmeUtility::getCardExpirationMonths(),
      '#default_value' => date('m'),
      '#required' => TRUE,
      '#prefix' => '<div class="commerce-credit-card-expiration">',
    ];
    $cc_expiration_container['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $cardYearData = PagarmeUtility::getCardExpirationYears();
    $cc_expiration_container['year_expiration'] = [
      '#type' => 'select',
      '#options' => $cardYearData['years'],
      '#default_value' => $cardYearData['current_year_4'],
      '#required' => TRUE,
    ];

    $form['pagarme_whitelabel']['credit_card']['cvv'] = [
      '#type' => 'textfield',
      '#title' => t('Security code'),
      '#size' => 5,
      '#maxlength' => 3,
      '#attributes' => ['autocomplete' => 'off', 'pattern' => '[0-9]*'],
    ];
    $max_installments = $pagarmeDrupal->getCreditCardMaxInstallments();
    if ($max_installments > 1) {
      $options = array();
      for ($i=1; $i <= $max_installments; $i++) { 
        $options[$i] = $i;
      }
      $installments_amount = $pagarmeDrupal->calculateInstallmentsAmount();
      $default_installment = $plugin_config['pagarme_default_installment'];
      if (is_array($values) && !empty($values['pagarme_whitelabel']['credit_card']['installments'])) {
        $default_installment = $values['pagarme_whitelabel']['credit_card']['installments'];
      }
      //Formatting portion amount
      $portion_amount = $installments_amount[$default_installment]['installment_amount'];
      $portion = ' x ' . PagarmeUtility::currencyAmountFormat($portion_amount, $currency_code, 'integer');
      //Formatting total amount
      $amount = $installments_amount[$default_installment]['total_amount'];
      $total = "Total: ". PagarmeUtility::currencyAmountFormat($amount, $currency_code, 'integer');
      $form['pagarme_whitelabel']['credit_card']['installments'] = [
        '#type' => 'select',
        '#title' => t('installment in'),
        '#options' => $options,
        '#default_value' => $default_installment,
        '#field_suffix' => $portion,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'effect' => 'fade',
          'wrapper' => 'pagarme-credit-card-installments',
        ],
        '#prefix' => '<div id="pagarme-credit-card-installments">',
        '#suffix' => '<span>'.$total.'</span></div>',
      ];
    } else {
      $form['pagarme_whitelabel']['credit_card']['installments'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];
    }
    return $form;
  }

   /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form['payment_process']['offsite_payment']['pagarme_whitelabel']['credit_card']['installments'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $pagarme_answer = json_decode($values['pagarme_whitelabel']['answer']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $plugin_config = $payment_gateway_plugin->getConfiguration();
    $pagarmeDrupal = new \Drupal\pagarme\Pagarme\PagarmeDrupal($plugin_config['pagarme_api_key']);
    $pagarmeDrupal->setPluginConfiguration($plugin_config);
    $order = $payment->getOrder();
    $pagarmeDrupal->setOrder($order);
    $order_data = [];
    $order_data['pagarme_payment_method'] = $pagarme_answer->pagarme_payment_method;

    //Dados do cliente
    $customer = [];
    $customer['address'] = [];
    $customer['phone'] = [];
    $token = \Drupal::token();
    if (!empty($plugin_config['pagarme_street_number_token'])) {
      $customer['address']['street_number'] = $token->replace($plugin_config['pagarme_street_number_token'], ['commerce_order' => $order]);
    }

    if (!empty($plugin_config['pagarme_cpf_token'])) {
      $customer['document_number'] = $token->replace($plugin_config['pagarme_cpf_token'], ['commerce_order' => $order]);
    } else if (!empty($plugin_config['pagarme_cnpj_token'])) {
      $customer['document_number'] = $token->replace($plugin_config['pagarme_cnpj_token'], ['commerce_order' => $order]);
    }

    if (!empty($plugin_config['pagarme_phone_ddd_token'])) {
      $customer['phone']['ddd'] = $token->replace($plugin_config['pagarme_phone_ddd_token'], ['commerce_order' => $order]);
    }

    if (!empty($plugin_config['pagarme_phone_number_token'])) {
      $customer['phone']['number'] = $token->replace($plugin_config['pagarme_phone_number_token'], ['commerce_order' => $order]);
    }

    if (!empty($plugin_config['pagarme_gender_token'])) {
      $customer['gender'] = $token->replace($plugin_config['pagarme_gender_token'], ['commerce_order' => $order]);
    }
    //TODO bug date field bug
    // if (!empty($plugin_config['pagarme_birthday_token'])) {
    //   $customer_born_at = $token->replace($plugin_config['pagarme_birthday_token'], array('commerce_order' => $order));
    //   $customer['born_at'] = date('m-d-Y', strtotime($customer_born_at));
    // }
    
    \Drupal::moduleHandler()->alter('pagarme_customer', $customer, $order);
    $pagarmeDrupal->setCustomer($customer);

    if ($plugin_config['pagarme_debug']) {
      \Drupal::logger('pagarme')->debug(t('@payment_way: <pre>@pre</pre>'), array('@pre' => print_r($pagarme_answer->pagarme_payment_method, TRUE)));
      \Drupal::logger('pagarme')->debug(t('@pagarme_customer: <pre>@pre</pre>'), array('@pre' => print_r($customer, TRUE)));
    }

    try {
      $card_hash = $pagarme_answer->card_hash;
      $installments = $values['pagarme_whitelabel']['credit_card']['installments'];
      $installments_amount = $pagarmeDrupal->calculateInstallmentsAmount();
      $amount = $installments_amount[$installments]['total_amount'];
      $order_data['pagarme_installments_amount'] = $installments_amount[$installments];
      $transaction = $pagarmeDrupal->creditCardTransaction($amount, $card_hash, $installments);
      if ($transaction->getStatus() == 'refused') {
        $message = t('Your purchase was declined, please check your payment information and try again or use another form of payment.');
        drupal_set_message($message, 'error');
        throw new \Exception($message);
      }

      $order_data['pagarme_payment_transaction_id'] = $transaction->getId();
      $order_data['pagarme_payment_config'] = $plugin_config;
      pagarme_transaction_data('pagarme_order_transaction', $order_data);

      $payment_gateway_plugin->createPayment($transaction, $order, $payment);
    } catch (\Exception $e) {
      \Drupal::logger('pagarme')->error($e->getMessage());
      drupal_set_message(t('There was an error with Pagar.me. Please try again later.'), 'error');
      $redirect_url = 'internal:/' . $token->replace($plugin_config['pagarme_checkout_failure_url'], array('commerce_order' => $order));
      $redirect_url = Url::fromUri($redirect_url, array('absolute' => TRUE))->toString();
      $response = new RedirectResponse($redirect_url);
      $response->send();
      exit;
    }
  }
}
