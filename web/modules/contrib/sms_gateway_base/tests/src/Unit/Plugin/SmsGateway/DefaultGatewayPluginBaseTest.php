<?php

namespace Drupal\Tests\sms_gateway_base\Unit\Plugin\SmsGateway;

use Drupal\sms_gateway_base\Plugin\SmsGateway\InvalidCommandException;
use Drupal\sms_gateway_test\Plugin\SmsGateway\FooLlamaGateway;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Unit tests the default gateway plugin base.
 *
 * @group SMS Gateways
 */
class DefaultGatewayPluginBaseTest extends UnitTestCase {

  use TestStringTranslationInterfaceTrait;

  /**
   * Tests the invalid command message.
   */
  public function testInvalidCommand() {
    $configuration = [];
    $definition = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
    ];
    $gateway_plugin = new FooLlamaGateway($configuration, $definition['id'], $definition);

    // Expect exception
    $this->setExpectedException(InvalidCommandException::class, 'Invalid command invalid');

    // Simulate an invalid command.
    $gateway_plugin->doInvalidCommand();
  }

}
