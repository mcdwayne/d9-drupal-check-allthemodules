<?php

namespace Drupal\commerce_adyen\Adyen\Authorisation;

use Adyen\Contract;
use Adyen\Service\Recurring;
use Drupal\commerce_adyen\Adyen\Client;
use Drupal\commerce_adyen\Adyen\ShopperInteraction;
use Drupal\commerce_adyen\Adyen\Controller\Payment;

/**
 * Payment authorisation request.
 */
class Request extends Signature {

  use Client;

  /**
   * Default endpoint to send the request.
   */
  const ENDPOINT_PAY = 'pay';
  /**
   * Send the request there to select the payment method.
   */
  const ENDPOINT_SELECT = 'select';
  /**
   * Send the request to edit the payment details.
   */
  const ENDPOINT_DETAILS = 'details';
  /**
   * Send request together with "brandCode" to skip payment method selection.
   */
  const ENDPOINT_SKIP_DETAILS = 'skipDetails';

  /**
   * Request endpoint.
   *
   * @var string
   */
  protected $endpoint = '';

  /**
   * Request constructor.
   *
   * @param \stdClass $order
   *   Commerce order.
   * @param array $payment_method
   *   Payment method information.
   */
  public function __construct(\stdClass $order, array $payment_method) {
    if (empty($payment_method['settings'])) {
      throw new \UnexpectedValueException(t('You are not configured Adyen payment gateway!'));
    }

    parent::__construct($order, $payment_method);
    // Wrapper can be obtained only after it'll be constructed.
    $order_wrapper = $this->getOrder();

    // Payment fields.
    // @link https://docs.adyen.com/developers/hpp-manual#hpppaymentfields
    $this->setMerchantAccount($payment_method['settings']['merchant_account']);
    $this->setMerchantReference($order->order_number);
    $this->setSkinCode($payment_method['settings']['skin_code']);
    // Currency code must be set before amount!
    $this->setCurrencyCode($order_wrapper->commerce_order_total['currency_code']);
    $this->setPaymentAmount($order_wrapper->commerce_order_total['amount']);
    $this->setSessionValidity(strtotime('+ 2 hour'));
    $this->setShopperIp(\Drupal::request()->getClientIp());
    $this->setShopperEmail($order->mail);
    // For "Anonymous" users we need to pass email as shopper reference, it was
    // flagged by the Adyen. Otherwise, we can get large fraud score.
    $this->setShopperReference($order->uid > 0 ? $order_wrapper->owner['name'] : $order->mail);
    $this->setShopperLocale($payment_method['settings']['shopper_locale']);
    $this->setCountryCode($order_wrapper->commerce_customer_billing['commerce_customer_address']['country']);
    $this->setShopperInteraction(ShopperInteraction::ECOMMERCE);
    $this->setMerchantReturnData($order->data['payment_redirect_key']);
    $this->setEndpoint(self::ENDPOINT_PAY);
    $this->setShipBeforeDate($order->ship_before_date);

    // The default result landing page shoppers are redirected to when
    // they complete a payment on the HPP. This value cannot be changed!
    $this->data['resURL'] = 'checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'];
  }

  /**
   * Set ship before date.
   *
   * @param string $ship_before_date
   *   For date limit for ship.
   */
  public function setShipBeforeDate($ship_before_date) {
    $this->data['ship_before_date'] = $ship_before_date;
  }

  /**
   * Get ship before date.
   *
   * @return mixed
   *   Date limit for ship.
   */
  public function getShipBeforeDate() {
    return $this->data['ship_before_date'];
  }

  /**
   * Get resUrl.
   *
   * @return mixed
   *   Result URL.
   */
  public function getResUrl() {
    return $this->data['resURL'];
  }

  /**
   * Set amount of a payment.
   *
   * @param int $payment_amount
   *   Payment amount. Specified in minor units.
   *
   * @throws \InvalidArgumentException
   */
  public function setPaymentAmount($payment_amount) {
    if (empty($this->data['currencyCode'])) {
      throw new \InvalidArgumentException(t('You must set currency code before setting the price!'));
    }

    $this->data['paymentAmount'] = commerce_adyen_amount($payment_amount, $this->data['currencyCode']);
  }

  /**
   * Get amount of a payment.
   *
   * @return string
   *   Amount of a payment.
   */
  public function getPaymentAmount() {
    return $this->data['paymentAmount'];
  }

  /**
   * Set currency code.
   *
   * @param string $currency_code
   *   Currency code.
   */
  public function setCurrencyCode($currency_code) {
    $this->data['currencyCode'] = $currency_code;
  }

  /**
   * Get currency code.
   *
   * @return string
   *   Currency code.
   */
  public function getCurrencyCode() {
    return $this->data['currencyCode'];
  }

  /**
   * Set brand code.
   *
   * @param string $brand_code
   *   Brand code.
   */
  public function setBrandCode($brand_code) {
    $this->data['brandCode'] = $brand_code;
  }

  /**
   * Set allowed payment methods.
   *
   * @param string[] $payment_methods
   *   A list of allowed payment methods.
   */
  public function setAllowedMethods(array $payment_methods) {
    $this->setPaymentMethods('allowed', $payment_methods);
  }

  /**
   * Set blocked payment methods.
   *
   * @param string[] $payment_methods
   *   A list of blocked payment methods.
   */
  public function setBlockedMethods(array $payment_methods) {
    $this->setPaymentMethods('blocked', $payment_methods);
  }

  /**
   * Get brand code of a payment.
   *
   * @return string
   *   Brand code of a payment.
   */
  public function getBrandCode() {
    return $this->data['brandCode'];
  }

  /**
   * Set merchant reference.
   *
   * @param string $merchant_reference
   *   Merchant reference.
   *
   * @example
   * $payment->setMerchantReference('DE-LW-2013');
   */
  public function setMerchantReference($merchant_reference) {
    $this->data['merchantReference'] = $merchant_reference;
  }

  /**
   * Get merchant reference.
   *
   * @return string
   *   Merchant reference.
   */
  public function getMerchantReference() {
    return $this->data['merchantReference'];
  }

  /**
   * Set skin code.
   *
   * @param string $skin_code
   *   Skin code that should be used for the payment.
   */
  public function setSkinCode($skin_code) {
    $this->data['skinCode'] = $skin_code;
  }

  /**
   * Get skin code.
   *
   * @return string
   *   Skin code.
   */
  public function getSkinCode() {
    return $this->data['skinCode'];
  }

  /**
   * Set merchant account.
   *
   * @param string $merchant_account
   *   The merchant account you want to process this payment with.
   */
  public function setMerchantAccount($merchant_account) {
    $this->data['merchantAccount'] = $merchant_account;
  }

  /**
   * Get merchant account.
   *
   * @return string
   *   Merchant account.
   */
  public function getMerchantAccount() {
    return $this->data['merchantAccount'];
  }

  /**
   * Set session validity.
   *
   * @param int $session_validity
   *   The final time by which a payment needs to have been made.
   */
  public function setSessionValidity($session_validity) {
    // No need to take care about the timezone because Drupal do this by itself.
    // @see drupal_session_regenerate()
    // @see drupal_session_initialize()
    $this->data['sessionValidity'] = date(DATE_ATOM, $session_validity);
  }

  /**
   * Get session validity.
   *
   * @return string
   *   Session validity.
   */
  public function getSessionValidity() {
    return $this->data['sessionValidity'];
  }

  /**
   * Set shopper email.
   *
   * @param string $shopper_email
   *   The email address of a shopper.
   */
  public function setShopperEmail($shopper_email) {
    $this->data['shopperEmail'] = $shopper_email;
  }

  /**
   * Get shopper email.
   *
   * @return string
   *   Shopper email.
   */
  public function getShopperEmail() {
    return $this->data['shopperEmail'];
  }

  /**
   * Set shopper reference.
   *
   * @param string $shopper_reference
   *   Shopper reference.
   *
   * @example
   * $payment->setShopperReference('admin');
   */
  public function setShopperReference($shopper_reference) {
    $this->data['shopperReference'] = $shopper_reference;
  }

  /**
   * Get shopper reference.
   *
   * @return string
   *   Shopper reference.
   */
  public function getShopperReference() {
    return $this->data['shopperReference'];
  }

  /**
   * Set shopper IP address.
   *
   * @param string $shopper_ip
   *   Shopper IP address.
   */
  public function setShopperIp($shopper_ip) {
    $this->data['shopperIP'] = $shopper_ip;
  }

  /**
   * Get shopper IP address.
   *
   * @return string
   *   Shopper IP address.
   */
  public function getShopperIp() {
    return $this->data['shopperIP'];
  }

  /**
   * Set shopper interaction.
   *
   * @param string $shopper_interaction
   *   Shopper interaction.
   *
   * @see \Commerce\Adyen\Payment\ShopperInteraction
   */
  public function setShopperInteraction($shopper_interaction) {
    $this->data['shopperInteraction'] = $shopper_interaction;
  }

  /**
   * Get shopper interaction.
   *
   * @return string
   *   Shopper interaction.
   */
  public function getShopperInteraction() {
    return $this->data['shopperInteraction'];
  }

  /**
   * Set return data.
   *
   * @param string $merchant_return_data
   *   This data will be passed back as-is on the return URL when the shopper
   *   completes (or abandons) the payment and returns to your shop.
   */
  public function setMerchantReturnData($merchant_return_data) {
    $this->data['merchantReturnData'] = $merchant_return_data;
  }

  /**
   * Get return data.
   *
   * @return string
   *   Return data.
   */
  public function getMerchantReturnData() {
    return $this->data['merchantReturnData'];
  }

  /**
   * Set country code.
   *
   * @param string $country_code
   *   Country code.
   *
   * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
   */
  public function setCountryCode($country_code) {
    $this->data['countryCode'] = strtoupper($country_code);
  }

  /**
   * Get country code.
   *
   * @return string
   *   Country code.
   */
  public function getCountryCode() {
    return $this->data['countryCode'];
  }

  /**
   * Set shopper locale.
   *
   * @param string $shopper_locale
   *   A combination of language code and country code to specify
   *   the language used in the session.
   */
  public function setShopperLocale($shopper_locale) {
    $this->data['shopperLocale'] = $shopper_locale;
  }

  /**
   * Get shopper locale.
   *
   * @return string
   *   Shopper locale.
   */
  public function getShopperLocale() {
    return $this->data['shopperLocale'];
  }

  /**
   * Set recurring contract type.
   *
   * @param string $recurring_contract
   *   Recurring contract type. Use one constants only. Keep in mind
   *   that PayPal does not allow to use "ONECLICK,RECURRING" type.
   * @param string $payment_reference
   *   PSP reference of a payment or "LATEST" to use latest one.
   *
   * @see \Adyen\Contract
   */
  public function setRecurringContract($recurring_contract, $payment_reference = 'LATEST') {
    // Shopper interaction must be set to "Ecomerce" for "ONECLICK"
    // contract type and to "ContAuth" for "RECURRING".
    // @see https://github.com/Adyen/php/blob/master/5.Recurring/soap/submit-recurring-payment.php
    if (Contract::RECURRING === $recurring_contract) {
      $this->setShopperInteraction(ShopperInteraction::CONTAUTH);
    }

    $this->data['recurringContract'] = $recurring_contract;
    $this->data['selectedRecurringDetailReference'] = $payment_reference;
  }

  /**
   * Get recurring contract type.
   *
   * @return string
   *   Recurring contract type.
   */
  public function getRecurringContract() {
    return $this->data['recurringContract'];
  }

  /**
   * Set endpoint type.
   *
   * @param string $endpoint
   *   An endpoint type to send the request.
   */
  public function setEndpoint($endpoint) {
    if (!in_array($endpoint, (new \ReflectionClass($this))->getConstants())) {
      throw new \InvalidArgumentException(t('Endpoint for payment request is incorrect!'));
    }

    $this->endpoint = $endpoint;
  }

  /**
   * Returns endpoint URL.
   *
   * @link https://docs.adyen.com/developers/hpp-manual#hppendpoints
   *
   * @return string
   *   Endpoint URL.
   */
  public function getEndpoint() {
    return sprintf('https://%s.Adyen.com/hpp/%s.shtml', $this->getPaymentMethod()['settings']['mode'], $this->endpoint);
  }

  /**
   * Get recurring contract details.
   *
   * @return array
   *   An array of data with recurring details.
   */
  public function getRecurringContractDetails() {
    $payment_method = $this->getPaymentMethod();
    $recurring = new Recurring($this->getClient($payment_method));

    return $recurring->listRecurringDetails([
      'merchantAccount' => $payment_method['settings']['merchant_account'],
      'shopperReference' => $this->getShopperReference(),
      'recurring' => [
        'contract' => $this->getRecurringContract(),
      ],
    ]);
  }

  /**
   * Extend payment authorisation request.
   *
   * @param \Drupal\commerce_adyen\Adyen\Controller\Payment $controller
   *   Object with additional payment data.
   */
  public function extend(Payment $controller) {
    $controller->setPayment($this);
    // This value MUST be saved into a variable, because payment controller
    // can modify values in "$this->data" and they are will not be available
    // for merging in case of passing the result directly as an argument.
    $controller_data = $controller->getData();

    $this->data = array_merge($this->data, $controller_data);
  }

  /**
   * Sign payment request.
   */
  public function signRequest() {
    $this->data['merchantSig'] = $this->getSignature();
  }

  /**
   * Get Merchant Signature.
   *
   * @return mixed
   *   Merchant Signature.
   */
  public function getMerchantSig() {
    return $this->data['merchantSig'];
  }

  /**
   * Set allowed/blocked payment methods.
   *
   * @param string $type
   *   One of available types: "allowed" or "blocked".
   * @param array $payment_methods
   *   A list of payment methods.
   *
   * @link https://docs.adyen.com/developers/api-reference/hosted-payment-pages-api#hpppaymentrequest
   */
  protected function setPaymentMethods($type, array $payment_methods) {
    $this->data[$type . 'Methods'] = implode(',', array_map('trim', $payment_methods));
  }

}
