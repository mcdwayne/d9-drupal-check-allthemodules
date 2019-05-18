<?php

namespace Drupal\commerce_partpay\PartPay;

/**
 * Provides a handler for IPN requests from PartPay.
 */
interface AbstractPartPayInterface {

  /**
   * Set PartPay configuration property.
   */
  public function setSettings(array $configuration);

  /**
   * Set PartPay configuration property.
   */
  public function getSettings($key = NULL);

  /**
   * Get PartPay clientId.
   */
  public function getClientId();

  /**
   * Set PartPay clientId.
   */
  public function setClientId($clientId);

  /**
   * Set PartPay secret.
   */
  public function setSecret($secret);

  /**
   * Get PartPay secret.
   */
  public function getSecret();

  /**
   * Get auth token.
   */
  public function getToken();

  /**
   * Set auth token expiry.
   */
  public function setTokenExpiry($expiry);

  /**
   * Get auth token expiry.
   */
  public function getTokenExpiry();

  /**
   * Set auth token.
   */
  public function setToken($token);

  /**
   * Delete auth tokens.
   */
  public function deleteTokens();

  /**
   * Set Test Mode.
   */
  public function setTestMode();

  /**
   * Is Test Mode.
   */
  public function isTestMode();

  /**
   * Set Token Mode.
   */
  public function setTokenRequestMode($mode);

  /**
   * Set Token Mode.
   */
  public function isTokenRequestMode();

  /**
   * Get API endpoint url.
   */
  public function getEndpoint();

  /**
   * Get Token endpoint url.
   */
  public function getTokenEndpoint();

  /**
   * Get Audience url.
   */
  public function getAudience();

  /**
   * Get merchant reference.
   */
  public function getReference();

  /**
   * Is it in redirection mode?
   */
  public function isRedirectMethod($response);

  /**
   * Get redirection url.
   */
  public function getRedirectUrl($response);

  /**
   * Is payment successful.
   */
  public function isSuccessful($response);

  /**
   * Http request.
   *
   * @param string $method
   *   Http request type ie. get, post.
   * @param string $resource
   *   The resource we are accessing.
   * @param array $options
   *   Addition request options.
   */
  public function request($method, $resource, array $options);

  /**
   * Http request.
   *
   * @param string $method
   *   Http request type ie. get, post.
   * @param string $resource
   *   The resource we are accessing.
   * @param array $options
   *   Addition request options.
   */
  public function handleRequest($method, $resource, array $options);

}
