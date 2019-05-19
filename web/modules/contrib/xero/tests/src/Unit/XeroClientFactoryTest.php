<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\xero\XeroClientFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests getting the XeroClient class.
 *
 * @coversDefaultClass \Drupal\xero\XeroClientFactory
 * @group Xero
 */
class XeroClientFactoryTest extends UnitTestCase {

  protected $pemFile;

  /**
   * @var \Drupal\xero\XeroClientFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->factory = new XeroClientFactory();
  }

  /**
   * Get xero configuration dummy data.
   *
   * @return []
   *   An associative array of cofiguration values to use.
   */
  protected function getConfiguration() {
    return [
      'consumer_key' => $this->getRandomGenerator()->string(32),
      'consumer_secret' => $this->getRandomGenerator()->string(32),
      'application' => 'private',
      'key_path' => __DIR__ . DIRECTORY_SEPARATOR . '../../fixtures/dummy.pem',
    ];
  }

  /**
   * Get the logger factory.
   *
   * @return \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected function getLoggerFactory() {
    $logProphet = $this->prophesize('\Psr\Log\LoggerInterface');
    $loggerFactory = new LoggerChannelFactory();
    $loggerFactory->addLogger($logProphet->reveal());
    return $loggerFactory;
  }

  /**
   * Test with valid configuration.
   */
  public function testValid() {
    $xero_config = $this->getConfiguration();
    $loggerFactory = $this->getLoggerFactory();
    $configProphet = $this->prophesize('\Drupal\Core\Config\ImmutableConfig');
    $configProphet->get(Argument::type('string'))->will(function ($args) use ($xero_config) {
      $key = str_replace('oauth.', '', $args[0]);
      return $xero_config[$key];
    });
    $configFactoryProphet = $this->prophesize('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactoryProphet->get('xero.settings')->willReturn($configProphet->reveal());

    if (!class_exists('\Radcliffe\Xero\XeroClient')) {
      $this->assertTrue(FALSE, 'XeroClient class is not found. Aborting test.');
      return;
    }

    $client = $this->factory->get($configFactoryProphet->reveal(), $loggerFactory);
    $this->assertTrue(is_a($client, '\Radcliffe\Xero\XeroClient'));
  }

  /**
   * Test with no configuration.
   */
  public function testNotValid() {
    $loggerFactory = $this->getLoggerFactory();
    $configProphet = $this->prophesize('\Drupal\Core\Config\ImmutableConfig');
    $configProphet->get(Argument::type('string'))->willReturn(NULL);
    $configFactoryProphet = $this->prophesize('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactoryProphet->get('xero.settings')->willReturn($configProphet->reveal());

    if (!class_exists('\Radcliffe\Xero\XeroClient')) {
      $this->assertTrue(FALSE, 'XeroClient class is not found. Aborting test.');
      return;
    }

    $client = $this->factory->get($configFactoryProphet->reveal(), $loggerFactory);
    $this->assertFalse($client, print_r($client, TRUE));
  }
}
