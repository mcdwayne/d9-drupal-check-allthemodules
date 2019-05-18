<?php

namespace Drupal\commerce_adyen\Adyen;

use Adyen\Client as AdyenClient;

/**
 * Adyen client.
 */
trait Client {

  /**
   * Get client.
   *
   * @param array $payment_method
   *   Payment method instance.
   *
   * @return \Adyen\Client
   *   Client object.
   *
   * @throws \Adyen\AdyenException
   */
  protected function getClient(array $payment_method) {
    $client = new AdyenClient();
    $client->setUsername($payment_method['settings']['client_user']);
    $client->setPassword($payment_method['settings']['client_password']);
    $client->setEnvironment($payment_method['settings']['mode']);

    return $client;
  }

}
