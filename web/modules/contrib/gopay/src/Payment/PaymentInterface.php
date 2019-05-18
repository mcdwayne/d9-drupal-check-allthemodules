<?php

namespace Drupal\gopay\Payment;

use Drupal\gopay\Contact\ContactInterface;
use Drupal\gopay\Item\ItemInterface;

/**
 * Interface PaymentInterface.
 *
 * @package Drupal\gopay\Payment
 */
interface PaymentInterface {

  /**
   * Sets return url.
   *
   * @param string $url
   *   URL.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setReturnUrl($url);

  /**
   * Sets notification url.
   *
   * @param string $url
   *   URL.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setNotificationUrl($url);

  /**
   * Sets default payment instrument.
   *
   * Use one of \GoPay\Definition\Payment\PaymentInstrument constants.
   *
   * @param string $payment_instrument
   *   Payment instrument.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setDefaultPaymentInstrument($payment_instrument);

  /**
   * Sets allowed payment instrument.
   *
   * Use \GoPay\Definition\Payment\PaymentInstrument constants.
   *
   * @param array|string $payment_instruments
   *   Array of PaymentInstrument constants.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setAllowedPaymentInstruments($payment_instruments);

  /**
   * Sets payer contact.
   *
   * @param \Drupal\gopay\Contact\ContactInterface $contact
   *   Contact object.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setContact(ContactInterface $contact);

  /**
   * Add additional parameter.
   *
   * @param array $additional_param
   *   Associative array ['name' => 'my_param', 'value' => 'my_param_value'].
   *
   * @see https://doc.gopay.com/cs/#additional_params
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function addAdditionalParam(array $additional_param);

  /**
   * Adds item in payment.
   *
   * @param \Drupal\gopay\Item\ItemInterface $item
   *   Item object.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function addItem(ItemInterface $item);

  /**
   * Sets amount.
   *
   * @param int $amount
   *   Amount of payment.
   * @param bool $in_cents
   *   Whether $amount is in cents or in units. GoPay API gets amount in cents,
   *   so if you enter amount in units, it will be converted to cents anyway.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setAmount($amount, $in_cents = TRUE);

  /**
   * Sets currency.
   *
   * Use one of \GoPay\Definition\Payment\Currency constants.
   *
   * @param string $currency
   *   Currency constant.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setCurrency($currency);

  /**
   * Sets order number.
   *
   * @param int $order_number
   *   Order number.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setOrderNumber($order_number);

  /**
   * Sets order description.
   *
   * @param string $order_description
   *   Order description.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setOrderDescription($order_description);

  /**
   * Sets payment language.
   *
   * @param string $lang
   *   Payment language.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   Returns itself.
   */
  public function setLang($lang);

  /**
   * Creates payment configuration compatible with GoPay SDK Payments.
   *
   * This array can be used directly with \Gopay\Payments::createPayment().
   *
   * @see https://doc.gopay.com/en/#standard-payment
   *
   * @return array
   *   Configuration of this Payment
   *
   * @throws \Drupal\gopay\Exception\GoPayInvalidSettingsException
   */
  public function toArray();

  /**
   * Builds render-able link to pay-gate.
   *
   * @param string $text
   *   Text of link.
   *
   * @return array
   *   Render-able array.
   */
  public function buildLink($text = NULL);

  /**
   * Builds render-able inline form to pay-gate.
   *
   * @param string $text
   *   Text of link.
   *
   * @return array
   *   Render-able array.
   */
  public function buildInlineForm($text = NULL);

}
