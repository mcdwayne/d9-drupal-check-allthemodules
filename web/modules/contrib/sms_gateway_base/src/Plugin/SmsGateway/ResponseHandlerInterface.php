<?php

namespace Drupal\sms_gateway_base\Plugin\SmsGateway;

/**
 * Normalizes HTTP response from a gateway to an SmsMessageResult object.
 */
interface ResponseHandlerInterface {

  /**
   * Handles the message response turning it to SmsMessageResult object.
   *
   * @param string $body
   *   The body of the response from the gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   An SMS message result object.
   */
  public function handle($body);

}
