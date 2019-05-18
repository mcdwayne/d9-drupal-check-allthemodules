<?php

namespace Drupal\commerce_payplug\Services;

/**
 * Interface description for PayPlug encapsulation service class.
 *
 * This class can be injected in requiring class, thus allow PayPlug service
 * unit/functional testing.
 *
 * @group commerce_payplug
 */
interface PayPlugServiceInterface {

  /**
   * Sets the API key in PayPlug service.
   *
   * @param string $api_key
   * @return \Payplug\Payplug  the new client authentication
   */
  public function setApiKey($api_key);

  /**
   * Creates a Payment.
   *
   * @param   array $data
   *    API data for payment creation
   * @param   \Payplug\Payplug $payplug
   *  the client configuration
   *
   * @return  null|\Payplug\Resource\Payment the created payment instance
   *
   * @throws  \Payplug\Exception\ConfigurationNotSetException
   */
  public function createPayPlugPayment(array $data, \Payplug\Payplug $payplug = null);

  /**
   * Treats a notification received from PayPlug service.
   *
   * @param   string  $requestBody     JSON Data sent by the notifier.
   * @param   \Payplug\Payplug $authentication  The client configuration.
   *
   * @return  \Payplug\Resource\IVerifiableAPIResource  A safe API Resource.
   *
   * @throws  \Payplug\Exception\UnknownAPIResourceException
   */
  public function treatPayPlugNotification($notification, $authentication = null);

}