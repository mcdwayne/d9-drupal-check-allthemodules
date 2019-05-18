<?php

namespace Drupal\pagarme\PagarMe;

use Drupal\Core\Url;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme_marketplace\Pagarme\PagarmeSplitRuleCollection;
use PagarMe\Sdk\Card\Card;
use PagarMe\Sdk\Customer\Address;
use PagarMe\Sdk\Customer\Customer;
use PagarMe\Sdk\Customer\Phone;
use PagarMe\Sdk\Recipient\Recipient;
use PagarMe\Sdk\SplitRule\SplitRuleCollection;

/**
 * @file Class encapsulating the business logic related to integrate Pagar.me with
 * Drupal structures.
 */
class PagarmeDrupal extends PagarmeSdk {

  protected $postback_url;

  protected $order;
  protected $customer;
  protected $checkout_params;

  public function __construct($api_key = null) {
    parent::__construct($api_key);
    $this->postback_url = Url::fromUri('internal:/pagarme/notification', array('absolute' => TRUE))->toString();
  }

  public function setOrder($order) {
    $this->order = $order;
  }

  public function getIntegerAmountFromOrder() {
    return $this->order->getTotalPrice()->getNumber() * 100;
  }

  /**
   *  É necessário passar os valores boolean em "$this->checkout_params" como string
   */
  public function processOrder() {

    // Cor primária da interface de Checkout
    $this->checkout_params['uiColor'] = $this->plugin_configuration['pagarme_ui_color'];

    // Habilita a geração do token para autorização da transação. 
    //OBS: Caso você queira apenas pegar os dados do cliente, deixe esse atributo com o valor false, e realize a transação normalmente no seu backend, com os dados informados no formulário do checkout.
    $this->checkout_params['createToken'] = 'false';

    $amount_integer = $this->getIntegerAmountFromOrder();

    // Endereço da URL de POSTback do seu sistema, que receberá as notificações das alterações de status das transações
    $this->checkout_params['postbackUrl'] = $this->postback_url;

    // Valor da transação (em centavos) a ser capturada pelo Checkout. Ex: R$14,79 = 1479
    $this->checkout_params['amount'] = $amount_integer;

    $this->addCustomerData();
    $this->applyPaymentSettings();

    return $this->checkout_params;
  }

  protected function addCustomerData() {
    // Definição de capturar de dados do cliente pelo Checkout
    if (!$this->plugin_configuration['pagarme_customer_data']) {
      $this->checkout_params['customerData'] = 'false';
    } else {
      // Não aceita CPF ou CNPJ em que todos os números são zeros, valor padrão false
      if ($this->plugin_configuration['pagarme_disable_zero_document_number']) {
        $this->checkout_params['disableZeroDocumentNumber'] = 'true';
      }

      $customer = $this->order->getCustomer();
      $profile = $this->order->getBillingProfile();
      $address = $profile->get('address')->first();
      $customer_name = $address->getGivenName() . ' ' . $address->getFamilyName();
      $this->checkout_params += array(
        'customerName' => $customer_name,
        'customerEmail' => $customer->getEmail(),
        'customerAddressStreet' => $address->getAddressLine1(),
        'customerAddressComplementary' => $address->getAddressLine2(),
        'customerAddressNeighborhood' => $address->getDependentLocality(),
        'customerAddressCity' => $address->getLocality(),
        'customerAddressState' => $address->getAdministrativeArea(),
        'customerAddressZipcode' => $address->getPostalCode(),
      );
    }
  }

  protected function applyPaymentSettings() {

    // Meios de pagamento disponíveis no Checkout.
    $payment_methods = $this->plugin_configuration['pagarme_payment_methods'];
    $this->checkout_params['paymentMethods'] = implode(',', array_filter($payment_methods));

    // Configurações cartão de crédito.
    if (in_array('credit_card', $payment_methods)) {

      // Bandeiras aceitas pelo Checkout.
      $card_brands = $this->plugin_configuration['pagarme_card_brands'];
      $this->checkout_params['cardBrands'] = implode(',', array_filter($card_brands));

      // Número máximo de parcelas aceitas, de 1 a 12.
      $this->checkout_params['maxInstallments'] = $this->getCreditCardMaxInstallments();

      // Define a parcela padrão selecionada ao abrir o checkout.
      $this->checkout_params['defaultInstallment'] = $this->plugin_configuration['pagarme_default_installment'];

      // Taxa de juros a ser cobrada na transação.
      if (!empty($this->plugin_configuration['pagarme_interest_rate'])) {
        $this->checkout_params['interestRate'] = $this->plugin_configuration['pagarme_interest_rate'];
      }

      // Número de parcelas que não terão juros cobrados.
      if (!empty($this->plugin_configuration['pagarme_free_installments'])) {
        $this->checkout_params['freeInstallments'] = $this->plugin_configuration['pagarme_free_installments'];
      }

      // Mensagem opcional que aparecerá embaixo do botão de pagamento Cartão de Crédito.
      if ($credit_card_helper_text = $this->plugin_configuration['pagarme_credit_card_helper_text']) {
        $this->checkout_params['creditCardHelperText'] = $credit_card_helper_text;
      }
    }

    // Configurações boleto
    if (in_array('boleto', $payment_methods)) {

      // Desconto boleto (Percentual/Valor em centavos)
      $discount_type = $this->plugin_configuration['pagarme_boleto_discount'];

      switch ($discount_type) {
        case 'amount':
          if ($boleto_discount_amount = $this->plugin_configuration['pagarme_boleto_discount_amount']) {
            if ($this->applyDiscount()) {
              $this->checkout_params['boletoDiscountAmount'] = $boleto_discount_amount;              
            }
          }
          break;
        case 'percentage':
          if ($percentage = $this->plugin_configuration['pagarme_boleto_discount_percentage']) {
            if ($this->applyDiscount()) {
              $this->checkout_params['boletoDiscountPercentage'] = $percentage;
            }
          }
          break;
      }

      // Mensagem opcional que aparecerá embaixo do botão de pagamento Boleto.
      if ($boleto_helper_text = $this->plugin_configuration['pagarme_boleto_helper_text']) {
        $this->checkout_params['boletoHelperText'] = $boleto_helper_text;
      }
    }
  }

  public function getCreditCardMaxInstallments() {
    $max_installments = $this->plugin_configuration['pagarme_max_installments'];

    // Validação para aplicar valor mínimo de pedido para parcelamento.
    if (!empty($this->plugin_configuration['pagarme_installment_start_value'])) {
      $order_amount = $this->getIntegerAmountFromOrder();

      $installment_start_value = (int) $this->plugin_configuration['pagarme_installment_start_value'];

      // Desativar o parcelamento se o valor total do pedido for menor que o valor mínimo configurado.
      if ($order_amount < $installment_start_value) {
        $max_installments = 1;
      }
    }
    return $max_installments;
  }

  public function calculateInstallmentsAmount() {
    $amount = $this->getIntegerAmountFromOrder();

    // Taxa de juros a ser cobrada na transação.
    $interest_rate = 0;
    if (!empty($this->plugin_configuration['pagarme_interest_rate'])) {
      $interest_rate = $this->plugin_configuration['pagarme_interest_rate'];
    }

    // Número de parcelas que não terão juros cobrados.
    $free_installments = 1;
    if (!empty($this->plugin_configuration['pagarme_free_installments'])) {
      $free_installments = $this->plugin_configuration['pagarme_free_installments'];
    }

    // Valor máximo de parcelas.
    $max_installments = $this->getCreditCardMaxInstallments();
    return $this->pagarme->calculation()->calculateInstallmentsAmount(
        $amount,
        $interest_rate,
        $free_installments,
        $max_installments
    );
  }

  public function calculateBoletoAmount() {
    $order_amount = $this->getIntegerAmountFromOrder();
    if ($this->applyDiscount()) {
      $discount_amount = 0;
      // Desconto boleto (Percentual/Valor em centavos)
      switch ($this->plugin_configuration['pagarme_boleto_discount']) {
        case 'amount':
          $discount_amount = $this->plugin_configuration['pagarme_boleto_discount_amount'];
          break;
        case 'percentage':
          $discount_percentage = $this->plugin_configuration['pagarme_boleto_discount_percentage'];
          if ($discount_percentage) {
            // Valor do desconto a ser aplicado
            $discount_amount = $order_amount * $discount_percentage / 100;
          }
          break;
      }
      return $order_amount - $discount_amount;
    }
    return $order_amount;
  }

  protected function applyDiscount() {
    // Valor do pedido
    $order_amount = $this->getIntegerAmountFromOrder();

    // Valor mínimo de pedido para aplicar desconto.Campo obrigatório apenas para descontos em centavos.
    $boleto_discount_start = $this->plugin_configuration['pagarme_boleto_discount_start'];
    if (!empty($boleto_discount_start)) {
      // O desconto só vai ser aplicado se o valor do pedido for maior que o valor mínimo configurado para aplicar desconto.
      if ($order_amount > (int) $boleto_discount_start) {
        return TRUE;
      }
      return FALSE;
    }
    return TRUE;
  }

  public function creditCardTransaction($amount, $card_hash, $installments) {
    $card = new Card(array('hash' => $card_hash));
    $capture = TRUE;
    $metadata = $this->transactionMetadata();

    $extra_attributes = array();
    $extra_attributes['async'] = FALSE;
    if (!empty($this->plugin_configuration['pagarme_async_transaction'])) {
      $extra_attributes['async'] = TRUE;
    }

    $obj = new \StdClass();
    $obj->payment_method = 'credit_card';
    $obj->amount = $amount;
    $split_rules = $this->splitRuleCollection($obj);
    $extra_attributes = array_merge($extra_attributes, $split_rules);

    /** @var $transaction \PagarMe\Sdk\Transaction\CreditCardTransaction */
    return $this->pagarme->transaction()->creditCardTransaction(
        $amount,
        $card,
        $this->customer,
        $installments,
        $capture,
        $this->postback_url,
        $metadata,
        $extra_attributes
    );
  }

  public function calculateCreditCardAmount($installments) {
    $installments_amount = $this->calculateInstallmentsAmount();
    return $installments_amount[$installments]['total_amount'];
  }

  public function boletoTransaction($amount) {    
    $metadata = $this->transactionMetadata();
    $extra_attributes = array();
    
    $obj = new \StdClass();
    $obj->payment_method = 'boleto';
    $obj->amount = $amount;
    $split_rules = $this->splitRuleCollection($obj);
    $extra_attributes = array_merge($extra_attributes, $split_rules);

    /** @var $transaction \PagarMe\Sdk\Transaction\BoletoTransaction */
    return $this->pagarme->transaction()->boletoTransaction(
        $amount,
        $this->customer,
        $this->postback_url,
        $metadata,
        $extra_attributes
    );
  }

  public function splitRuleCollection($data) {
    $module_handler = \Drupal::service('module_handler');
    $rules = array();
    if ($module_handler->moduleExists('pagarme_marketplace')) {
      $split_rule_collection = new PagarmeSplitRuleCollection(
          $this->order,
          $data->payment_method,
          $data->amount
      );
      $rules = $split_rule_collection->doSplitRuleCollection();
    }
    return $rules;
  }

  public function transactionMetadata() {
    $metadata = array();
    $order = $this->order;
    $metadata = array(
      'order_id' => $order->id(),
    );
    \Drupal::moduleHandler()->alter('pagarme_metadata', $metadata, $order);
    return $metadata;
  }

  /**
   * Set the customer property
   * @param array $customer
   * @return void
   */
  public function setCustomer($customer) {
    $customer_data = array();
    $data_address = array();
    $data_phone = array();

    $customer_order = $this->order->getCustomer();
    $profile = $this->order->getBillingProfile();
    $address = $profile->get('address')->first();

    $customer_data['name'] = '';
    if (!empty($customer['name'])) {
      $customer_data['name'] = $customer['name']; 
    } else if (!empty($address->getGivenName())) {
      $customer_data['name'] =$address->getGivenName() . ' ' . $address->getFamilyName();
    }

    $customer_data['email'] = '';
    if (!empty($customer['email'])) {
      $customer_data['email'] = $customer['email']; 
    } else if (!empty($customer_order->getEmail())) {
      $customer_data['email'] = $customer_order->getEmail();
    }

    $data_address['street'] = '';
    if (!empty($customer['address']['street'])) {
      $data_address['street'] = $customer['address']['street']; 
    } else if (!empty($address->getAddressLine1())) {
      $data_address['street'] =$address->getAddressLine1();
    }

    $data_address['streetNumber'] = '';
    if (!empty($customer['address']['street_number'])) {
      $data_address['streetNumber'] = $customer['address']['street_number'];
    }

    $data_address['complementary'] = '';
    if (!empty($customer['address']['complementary'])) {
      $data_address['complementary'] = $customer['address']['complementary']; 
    } else if (!empty($address->getAddressLine2())) {
      $data_address['complementary'] = $address->getAddressLine2();
    }

    $data_address['neighborhood'] = '';
    if (!empty($customer['address']['neighborhood'])) {
      $data_address['neighborhood'] = $customer['address']['neighborhood']; 
    } else if (!empty($address->getDependentLocality())) {
      $data_address['neighborhood'] = $address->getDependentLocality();
    }

    $data_address['city'] = '';
    if (!empty($customer['address']['city'])) {
      $data_address['city'] = $customer['address']['city']; 
    } else if (!empty($address->getLocality())) {
      $data_address['city'] =$address->getLocality();
    }

    $data_address['state'] = '';
    if (!empty($customer['address']['state'])) {
      $data_address['state'] = $customer['address']['state']; 
    } else if (!empty($address->getAdministrativeArea())) {
      $data_address['state'] = $address->getAdministrativeArea();
    }

    $data_address['zipcode'] = '';
    if (!empty($customer['address']['zipcode'])) {
      $data_address['zipcode'] = $customer['address']['zipcode']; 
    } else if (!empty($address->getPostalCode())) {
      $data_address['zipcode'] = $address->getPostalCode();
    }

    $data_address['country'] = '';
    if (!empty($customer['address']['country'])) {
      $data_address['country'] = $customer['address']['country']; 
    } else if (!empty($address->getCountryCode())) {
      $data_address['country'] = $address->getCountryCode();
    }

    $data_phone = array(
      'ddd' => (!empty($customer['phone']['ddd'])) ? $customer['phone']['ddd'] : '',
      'number' => (!empty($customer['phone']['number'])) ? $customer['phone']['number'] : '',
      'ddi' => 55,
    );

    $customer_data += array(
      'document_number' => (!empty($customer['document_number'])) ? $customer['document_number'] : '',
      'gender' => (!empty($customer['gender'])) ? $customer['gender'] : '',
      'bornAt' => (!empty($customer['born_at'])) ? $customer['born_at'] : '',
    );

    $customer_data += array(
      'address' => new Address($data_address),
      'phone' => new Phone($data_phone),
    );

    $this->customer = new Customer($customer_data);
  }

  public function getCustomer() {
    return $this->customer;
  }

  public function dataCustomer($pagarme_cp_answer) {
    $customer_data = array();
    $data_address = array();
    $data_phone = array();

    $customer = $pagarme_cp_answer->customer;
    if (!empty($customer->address)) {
      $customer_data += array(
        'name' => $customer->name,
        'email' => $customer->email,
        'document_number' => $customer->document_number
      );

      $data_address = array(
        'street' => $customer->address->street,
        'neighborhood' => $customer->address->neighborhood,
        'zipcode' => $customer->address->zipcode,
        'complementary' => $customer->address->complementary,
        'city' => $customer->address->city,
        'state' => $customer->address->state,
        'country' => 'Brasil',
      );

    } else {
      $customer_data += array(
        'name' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->name_line->value(),
        'email' => $this->order_wrapper->mail->value(),
        'document_number' => $customer->document_number,
      );

      // Pagar.me needs the thoroughfare number to be separate from the street
      $thoroughfare = explode(',', $this->order_wrapper->commerce_customer_billing->commerce_customer_address->thoroughfare->value());

      $data_address += array(
        'street' => trim($thoroughfare[0]),
        'streetNumber' => trim($thoroughfare[1]),
        'complementary' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->premise->value(),
        'neighborhood' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->dependent_locality->value(),
        'city' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->locality->value(),
        'state' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->administrative_area->value(),
        'zipcode' => $this->order_wrapper->commerce_customer_billing->commerce_customer_address->postal_code->value(),
        'country' => 'Brasil',
      );      

    }

    $data_address['streetNumber'] = $customer->address->street_number;

    $data_phone = array(
      'ddd' => $customer->phone->ddd,
      'number' => $customer->phone->number,
      'ddi' => 55,
    );

    $customer_data += array(
      'gender' => $customer->gender,
      'bornAt' => $customer->born_at,
    );
    $customer_data += array(
      'address' => new Address($data_address),
      'phone' => new Phone($data_phone),
    );
    return new Customer($customer_data);
  }

  public function captureTransactions($pagarme_cp_answer) {
    $authorizedTransaction = $this->pagarme->transaction()->get($pagarme_cp_answer->token);

    $amount_integer = $this->order_wrapper->commerce_order_total->amount->value();
    return $this->pagarme->transaction()->capture($authorizedTransaction, $amount_integer);
  }
}
