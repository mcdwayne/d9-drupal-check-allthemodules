<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides mappings for Pagseguro's status codes.
 **/
class PagseguroPaymentHandler {

  protected $mode;

  protected $token;

  protected $email;

  protected $no_interest_installment_quantity;

  protected $email_buyer_sandbox;

  protected $field_birthdate;

  protected $field_cpf;

  protected $field_full_name;

  protected $field_telephone;

  private $payment_method;

  private $payment_request;

  private $customer;

  public $order;

  public $payment_method_type;

  public function getToken() {
    return $this->token;
  }

  public function getEmail() {
    return $this->email;
  }

  public function getNoInterestInstallmentQuantity() {
    return $this->no_interest_installment_quantity;
  }

  public function getEmailBuyer() {
    return $this->email_buyer_sandbox;
  }

  public function getFieldCpf() {
    return $this->field_cpf;
  }

  public function getMode() {
    return $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(OrderInterface $order, PaymentMethodInterface $payment_method) {
    $this->order = $order;
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $this->payment_method = $payment_method;
    $this->configuration = $payment_method->getPaymentGateway()->getPluginConfiguration();
    $this->customer = $order->getCustomer();
    $payment_method_type = $this->payment_method->getType()->getPluginId();

    // Setting properties by configuration.
    $this->mode = $this->configuration['mode'];
    $this->no_interest_installment_quantity = $this->configuration['no_interest_installment_quantity'];
    $this->email = $this->configuration['email'];
    $this->email_buyer_sandbox = $this->configuration['email_buyer_sandbox'];
    $this->field_birthdate = $this->configuration['field_birthdate'];
    $this->field_cpf = $this->configuration['field_cpf'];
    $this->field_full_name = $this->configuration['field_full_name'];
    $this->field_telephone = $this->configuration['field_telephone'];

    // Setting the token to live or test.
    $sandbox = ($this->getMode() == 'test');
    if ($sandbox) {
      $this->token = $this->configuration['token_sandbox'];
    }
    else {
      $this->token = $this->configuration['token'];
    }
  }

  private function initializePagseguro() {
    // Initializing pagseguro
    try {
      \PagSeguro\Library::initialize();
    } catch (\Exception $e) {
      die($e->getMessage());
    }

    \PagSeguro\Library::cmsVersion()->setName("Nome")->setRelease("1.0.0");
    \PagSeguro\Library::moduleVersion()->setName("Nome")->setRelease("1.0.0");
    // UTF-8 or ISO-8859-1
    \PagSeguro\Configuration\Configure::setCharset('UTF-8');
    //\PagSeguro\Configuration\Configure::setLog(true, '/pagseguro.log');

    $environment = ($this->getMode() == 'test') ? 'sandbox' : 'production';
    // Production or sandbox.
    \PagSeguro\Configuration\Configure::setEnvironment($environment);
    \PagSeguro\Configuration\Configure::setAccountCredentials(
      $this->getEmail(),
      $this->getToken()
    );
  }

  /**
   * Processes the payment request.
   *
   * @param OrderInterface $order
   *   The order being processed.
   *
  * @param PaymentMethodInterface $payment_method
   *   The payment method processing the order.
   *
   * @return
   *   The result from Pagseguro's API.
   * @throws \Exception
   */
  public function payPagseguro() {
    try {
      // Initialise Pagseguro.
      $this->initializePagseguro();

      // Initialise the payment request for each payment method.
      $payment_method_type = $this->payment_method->getType()->getPluginId();

      switch ($payment_method_type) {
        case 'pagseguro_credit_card':

          $this->payment_request = new \PagSeguro\Domains\Requests\DirectPayment\CreditCard();
          $this->setBillingAddress();
          $this->setInstallments();

          // Set Customer information.
          $this->setCustomerBirthdate();
          $this->setCustomerCardHolderName();
          $this->setCustomerCPF();
          $this->setCustomerPhoneNumber();

          // Set credit card token.
          $this->payment_request->setToken($this->payment_method->getRemoteId());
          break;

        case 'pagseguro_boleto':
          // Instantiate a new Pagseguro Boleto object.
          $this->payment_request = new \PagSeguro\Domains\Requests\DirectPayment\Boleto();
          break;

        case 'pagseguro_debit':
          // Instantiate a new Pagseguro Online Debit object.
          $this->payment_request = new \PagSeguro\Domains\Requests\DirectPayment\OnlineDebit();

          if (!empty($this->payment_method->get('bank_name')->first())) {
            $bank = $this->payment_method->get('bank_name')->first()->getString();

            // Set bank for this payment request.
            $this->payment_request->setBankName($bank);
          }
          break;
      }

      $this->payment_request->setReceiverEmail($this->getEmail());

      // Set a reference code for this payment request. It is useful to identify this payment
      // in future notifications.
      $this->payment_request->setReference($this->order->id());

      // Set the currency.
      $this->payment_request->setCurrency("BRL");

      $this->setOrderItems($this->order);
      

      // Set extra amount.
      //$payment_request->setExtraAmount(11.5);

      $this->setSenderEmail();
      $this->setSenderPhoneNumber();
      $this->setSenderCPF();
      $this->setSenderHash();

      $this->setShippingDetails();

      // Set the Payment Mode for this payment request
      $this->payment_request->setMode('DEFAULT');

      $this->payment_request->setRedirectUrl(
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath()
      );

      $payment_gateway_id = $this->payment_method->getPaymentGatewayId();
      $this->payment_request->setNotificationUrl(
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath() .
        "/payment/notify/$payment_gateway_id"
      );
    } catch (MissingDataException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    // Send the payment request to Pagseguro.
    try {
      //Get the credentials and register payment
      $result = $this->payment_request->register(
        \PagSeguro\Configuration\Configure::getAccountCredentials()
      );
      return $result;
    } catch (\Exception $e) {

      $return_error = simplexml_load_string($e->getMessage(), 'SimpleXMLElement');

      foreach ($return_error->error as $error) {
        $code = $error->code->__toString();
        $message = $error->message->__toString();
        drupal_set_message(t('Code: ') . $code . t('. Message: ') . $message, 'error');
      }

      return 0;
    }
  }

  public function registerPaymentTransaction($payment, $pagseguro_response) {

    $payment_method_type = $this->payment_method->getType()->getPluginId();

    // Payment complete.
    if ($pagseguro_response) {
      if ($payment_method_type == 'pagseguro_debit' || $payment_method_type == 'pagseguro_boleto') {
        /** @var \PagSeguro\Parsers\Response\PaymentLink $response */
        $this->payment_method->set('payment_link', $pagseguro_response->getPaymentLink());
        $this->payment_method->save();
      }

      $payment->set('type', $payment_method_type);
      $payment->set('test', $this->getMode() == 'test');
      $payment->setState('new');
      /** @var \PagSeguro\Domains\Responses\PaymentMethod $response */
      $payment->setRemoteId($pagseguro_response->getCode());
      $payment->save();

    // Payment error.
    }
    else {
      $this->payment_method->delete();
      throw new PaymentGatewayException('The provided payment method is no longer valid');
    }
  }

  public function registerLightboxPaymentTransaction($payment) {

    $payment_method_type = $this->payment_method->getType()->getPluginId();

    // Payment complete.
      $payment->set('type', $payment_method_type);
      $payment->set('test', $this->getMode() == 'test');
      $payment->setState('new');
      /** @var \PagSeguro\Domains\Responses\PaymentMethod $response */
      //$payment->setRemoteId($response->getCode());
      $payment->save();
  }

  /**
   * Processes the payment request.
   *
   * @param OrderInterface $order
   *   The order being processed.
   *
  * @param PaymentMethodInterface $payment_method
   *   The payment method processing the order.
   *
   * @return
   *   The result from Pagseguro's API.
   * @throws \Exception
   */
  public function payPagseguroLightbox() {
    try {
      // Initialise Pagseguro.
      $this->initializePagseguro();

      $this->payment_request = new \PagSeguro\Domains\Requests\Payment();
      $this->setOrderItems($this->order);

      $this->payment_request->setReference($this->order->id());

      // Set the currency.
      $this->payment_request->setCurrency("BRL");

      $this->setSenderEmail();
      $this->setSenderPhoneNumber();
      $this->setSenderCPF();
      $this->setSenderHash();

      $this->setShippingDetails();

      // Set the Payment Mode for this payment request
      //$this->payment_request->setMode('DEFAULT');

      $this->payment_request->setRedirectUrl(
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath()
      );

      $payment_gateway_id = $this->payment_method->getPaymentGatewayId();
      $this->payment_request->setNotificationUrl(
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath() .
        "/payment/notify/$payment_gateway_id"
      );
    } catch (MissingDataException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    // Send the payment request to Pagseguro.
    try {
      //Get the credentials and register payment
      $result = $this->payment_request->register(
        \PagSeguro\Configuration\Configure::getAccountCredentials(),
        TRUE
      );

      return $result;
    } catch (\Exception $e) {

      $return_error = simplexml_load_string($e->getMessage(), 'SimpleXMLElement');

      foreach ($return_error->error as $error) {
        $code = $error->code->__toString();
        $message = $error->message->__toString();
        drupal_set_message(t('Code: ') . $code . t('. Message: ') . $message, 'error');
      }

      return 0;
    }
  }

  public function addPagseguroLightBoxToForm(&$form, $pagseguro_transaction_code) {

     $form['#attached']['drupalSettings']['commercePagseguro']['sessionId'] = $pagseguro_transaction_code;
     // Adding PagSeguro library according environment.
      if ($this->getMode() == 'test') {
        $form['#attached']['library'][] = 'commerce_pagseguro/pagseguro_lightbox_sandbox';
      }
      else {
        $form['#attached']['library'][] = 'commerce_pagseguro/pagseguro_lightbox_production';
      }
      $form['#attached']['library'][] = 'commerce_pagseguro/commerce_pagseguro_lightbox';
  }

  private function setBillingAddress() {
    // Get billing information for credit card.
    if (!empty($this->payment_method->getBillingProfile()->get('address')->first())) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
      $billing_address = $this->payment_method->getBillingProfile()->get('address')->first();

      $this->payment_request->setBilling()->setAddress()->withParameters(
        $billing_address->getAddressLine1(),
        '111',
        $billing_address->getDependentLocality(),
        $billing_address->getPostalCode(),
        $billing_address->getLocality(),
        $billing_address->getAdministrativeArea(),
        'BRA'
      );
    }
  }

  private function setInstallments () {
    // Set the installment quantity and amount.
    if (!empty($this->payment_method->get('installments_qty')->first()->getString() && $this->payment_method->get('installment_amount')->first()->getString())) {
      $this->payment_request->setInstallment()->withParameters(
        $this->payment_method->get('installments_qty')->first()->getString(),
        number_format($this->payment_method->get('installment_amount')->first()->getString(), 2, '.', ''),
        (integer) $this->getNoInterestInstallmentQuantity()
      );
    }
  }

  private function setCustomerBirthdate() {
    if (!empty($this->customer->get($this->field_birthdate)->first())) {
      $birthdate = date_create($this->customer->get($this->field_birthdate)
        ->first()
        ->getString());
      $birthdate = date_format($birthdate,"d/m/Y");
      $this->payment_request->setHolder()->setBirthdate($birthdate);
    }
  }

  private function setCustomerCardHolderName() {
    if(!empty($this->payment_method->get('card_holder_name')->first())) {
      $this->payment_request->setHolder()->setName($this->payment_method->get('card_holder_name')
        ->first()
        ->getString());
    }
  }

  private function setCustomerCPF() {
    if (!empty($this->customer->get($this->field_cpf)->first())) {
      $this->payment_request->setHolder()->setDocument()->withParameters(
        'CPF',
        $this->customer->get($this->field_cpf)->first()->getString()
      );
    }

    if (!empty($this->payment_method->get('cpf')->first())) {
      $this->payment_request->setHolder()->setDocument()->withParameters(
        'CPF',
        $this->payment_method->get('cpf')->first()->getString()
      );
    }
  }

  private function setCustomerPhoneNumber() {
    if (!empty($this->customer->get($this->field_telephone)->first())) {
      $phone_number = $this->formatPhone($this->customer->get($this->field_telephone)
        ->first()
        ->getString());
    }
    if (isset($phone_number)) {
      $this->payment_request->setHolder()->setPhone()->withParameters(
        $phone_number['area_code'],
        $phone_number['phone']
      );
    }
  }

  private function setOrderItems($order) {
    foreach ($order->getItems() as $order_item) {
      $this->payment_request->addItems()->withParameters(
        $order_item->id(),
        $order_item->getTitle(),
        (integer) $order_item->getQuantity(),
        number_format($order_item->getUnitPrice()->getNumber(), 2, '.', '')
      // $order_item->setWeight($weight)
      // $order_item->setShippingCost($shippingCost)
      );
    }
  }

  private function setSenderEmail() {
    // Set the correct information according the mode (test or live).
    if ($this->getMode() == 'test') {
      $this->payment_request->setSender()->setName('Test Customer');

      $email_buyer = $this->getEmailBuyer();
      if($email_buyer) {
        $this->payment_request->setSender()->setEmail($email_buyer);
      } 
      else {
        $this->payment_request->setSender()->setEmail('testcustomer@sandbox.pagseguro.com.br');
      }
    }
    else {
      if (!empty($this->customer->get($this->field_full_name)->first())) {
        $this->payment_request->setSender()->setName($this->customer->get($this->field_full_name)
          ->first()
          ->getString());
      }
      $this->payment_request->setSender()->setEmail($this->customer->getEmail());
    }
  }

  private function setSenderPhoneNumber() {
    if (!empty($this->customer->get($this->field_telephone)->first())) {
      $phone_number = $this->formatPhone($this->customer->get($this->field_telephone)
        ->first()
        ->getString());
    }
    if (isset($phone_number)) {
      $this->payment_request->setSender()->setPhone()->withParameters(
        $phone_number['area_code'],
        $phone_number['phone']
      );
    }
  }

  private function setSenderCPF() {

    if (!empty($this->customer->get($this->field_cpf)->first())) {
      $this->payment_request->setSender()->setDocument()->withParameters(
        'CPF',
        $this->customer->get($this->field_cpf)->first()->getString()
      );
    }

    if (!empty($this->payment_method->get('cpf')->first())) {
      $this->payment_request->setSender()->setDocument()->withParameters(
        'CPF',
        $this->payment_method->get('cpf')->first()->getString()
      );
    }
  }

  private function setSenderHash() {
    if (!empty($this->payment_method->get('sender_hash')->first())) {
      $this->payment_request->setSender()->setHash($this->payment_method->get('sender_hash')
        ->first()
        ->getString());
    }
  }

  private function setShippingDetails() {
    // Set shipping information for this payment request if shipping module is installed.
    if (\Drupal::moduleHandler()->moduleExists('commerce_shipping')) {

      $this->payment_request->setShipping()->setAddress()->withParameters(
        'Av. Paulista',
        '1499',
        'Bela Vista',
        '01311200',
        'São Paulo',
        'SP',
        'BRA',
        'apto. 111'
      );

      // $payment_request->setShipping()->setCost()->withParameters(30.00);
      // $payment_request->setShipping()->setType()->withParameters(\PagSeguro\Enum\Shipping\Type::SEDEX);
    } else {
      $this->payment_request->setShipping()->setAddressRequired()->withParameters('FALSE');
    }
  }

  private function formatPhone($phone_number) {
    $area_code = substr($phone_number, 1, 2);
    $phone = substr($phone_number, 3);
    $phone = preg_replace("/[^0-9\s]/", "", $phone);
    $result['area_code'] = $area_code;
    $result['phone'] = $phone;

    return $result;
  }
}