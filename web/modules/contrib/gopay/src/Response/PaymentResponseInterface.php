<?php

namespace Drupal\gopay\Response;

/**
 * Interface PaymentResponseInterface.
 *
 * @package Drupal\gopay\Response
 */
interface PaymentResponseInterface {

  /**
   * Payment ID.
   *
   * @return int
   *   Payment ID.
   */
  public function getId();

  /**
   * Alphanumeric characters only.
   *
   * @return string
   *   Order Number.
   */
  public function getOrderNumber();

  /**
   * Response state.
   *
   * @see https://doc.gopay.com/en/?php#payment-status
   *
   * @return string
   *   Response state.
   */
  public function getState();

  /**
   * Response substate.
   *
   * @see https://doc.gopay.com/en/?php#payment-substate
   *
   * @return string
   *   Response substate.
   */
  public function getSubState();

  /**
   * Amount > 0.
   *
   * @return int
   *   Payment amount.
   */
  public function getAmount();

  /**
   * Payment currency.
   *
   * @see https://doc.gopay.com/en/?php#currency
   *
   * @return string
   *   Payment currency.
   */
  public function getCurrency();

  /**
   * Payment method.
   *
   * @see https://doc.gopay.com/en/?php#payment_instrument
   *
   * @return string
   *   Payment method.
   */
  public function getPaymentInstrument();

  /**
   * All payer info.
   *
   * @see https://doc.gopay.com/en/?php#payer
   *
   * @return object
   *   Payer info.
   */
  public function getPayer();

  /**
   * Target of payment.
   *
   * @see https://doc.gopay.com/en/?php#target
   *
   * @return object
   *   Target of payment info.
   */
  public function getTarget();

  /**
   * Additional parameters of payment.
   *
   * @see https://doc.gopay.com/en/?php#additional_params
   *
   * @return object
   *   Additional payment parameters.
   */
  public function getAdditionalParams();

  /**
   * Return additional parameter value for given name.
   *
   * @param string $name
   *   Name of additional parameter.
   *
   * @return mixed|null
   *   Return value for given name or NULL.
   */
  public function getAdditionalParam($name);

  /**
   * Payment language.
   *
   * @see https://doc.gopay.com/en/?php#lang
   *
   * @return string
   *   Payment language.
   */
  public function getLang();

  /**
   * URL for redirecting to the payment gateway.
   *
   * @return string
   *   Redirect url.
   */
  public function getGwUrl();

  /**
   * Return decoded json response.
   *
   * @return array
   *   Decoded json.
   */
  public function getResponseJson();

  /**
   * Return TRUE if http response is 200.
   *
   * @return bool
   *   Http response bool.
   */
  public function hasSucceed();

  /**
   * TRUE if paid success.
   *
   * @return bool
   *   Payment success.
   */
  public function isPaid();

  /**
   * TRUE if paid cancelled.
   *
   * @return bool
   *   Payment cancel.
   */
  public function isCancelled();

}
