<?php

namespace Drupal\sms_gateway_base\Plugin\SmsGateway;

/**
 * Determines that an SmsGateway plugin has a testing functionality.
 */
interface TestableGatewayPluginInterface {

  /**
   * Tests the gateway plugin.
   *
   * @param array $config
   *    Optional configuration parameters to test gateway with.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   An SMS message result object.
   */
  public function test(array $config = NULL);

}
