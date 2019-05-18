<?php

namespace Drupal\braintree_api;

/**
 * Enables use of the Braintree API library as a Symfony service.
 */
interface BraintreeApiServiceInterface {

  const ENVIRONMENT_SANDBOX = 'sandbox';
  const ENVIRONMENT_PRODUCTION = 'production';

  /**
   * Get the Braintree gateway.
   *
   * @return \Braintree\Gateway
   *   The Braintree gateway object, which is used to interact with Braintree.
   */
  public function getGateway();

  /**
   * Get the current configured Braintree environment.
   *
   * @return string
   *   one of "sandbox" or "production"
   */
  public function getEnvironment();

  /**
   * Get Merchant ID.
   *
   * @return string|null
   *   The Braintree Merchant ID.
   */
  public function getMerchantId();

  /**
   * Get private key.
   *
   * @return string|null
   *   The Braintree Private Key.
   */
  public function getPrivateKey();

  /**
   * Get public key.
   *
   * @return string|null
   *   The Braintree Public Key.
   */
  public function getPublicKey();

}
