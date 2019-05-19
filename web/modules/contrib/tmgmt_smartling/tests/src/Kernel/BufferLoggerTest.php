<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Test BufferLogger.
 *
 * @group tmgmt_smartling
 */
class BufferLoggerTest extends SmartlingTestBase {

  protected $httpClient;
  protected $channel;
  protected $logger;

  public static $modules = ['system'];

  public function setUp() {
    parent::setUp();

    $this->httpClient = $this->getMockBuilder('GuzzleHttp\Client')
      ->setMethods([
        'request',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $this->channel = new LoggerChannel('tmgmt_smartling');
    $this->logger = \Drupal::getContainer()->get('logger.smartling');
    $this->channel->addLogger($this->logger);

    $this->setPrivatePropertyValue($this->logger, 'httpClient', $this->httpClient);
  }

  public function testLog() {
    $this->setPrivatePropertyValue($this->logger, 'providerSettings', [
      'settings' => [
        'project_id' => 'test_project_id',
      ]
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        'https://api.smartling.com/updates/status',
        $this->callback(
          function($subject) {
            return count($subject['json']['records']) == 1 &&
            $subject['json']['records'][0]['level_name'] == 'info' &&
            $subject['json']['records'][0]['channel'] == 'drupal-tmgmt-connector' &&
            $subject['json']['records'][0]['context']['projectId'] == 'test_project_id' &&
            preg_match('/(\d+\.x-\d+\.\d+|\d+\.x-\d+\.x-dev|\d\.\d\.\d-rc\d$|unknown)/', $subject['json']['records'][0]['context']['moduleVersion']) &&
            preg_match('/^tmgmt_extension_suit\/(.*) tmgmt\/(.*)$/', $subject['json']['records'][0]['context']['dependencies']) &&
            $subject['json']['records'][0]['context']['remoteChannel'] == 'tmgmt_smartling' &&
            $subject['json']['records'][0]['message'] == 'Test log record 1: info';
          }
        )
      );

    $this->channel->log(RfcLogLevel::INFO, 'Test log record 1: info');
    $this->logger->flush();
  }

  protected function setPrivatePropertyValue($object, $property, $value) {
    $reflection = new \ReflectionClass($object);
    $property = $reflection->getProperty($property);
    $property->setAccessible(true);
    $property->setValue($object, $value);
  }
}
