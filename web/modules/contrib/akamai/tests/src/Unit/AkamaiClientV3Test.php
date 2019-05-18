<?php

namespace Drupal\Tests\akamai\Unit;

use Drupal\akamai\KeyProviderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\akamai\Plugin\Client\AkamaiClientV3
 *
 * @group Akamai
 */
class AkamaiClientV3Test extends UnitTestCase {

  /**
   * Creates a client to test.
   *
   * @param array $config
   *   An array of client configuration.
   *
   * @return \Drupal\akamai\Plugin\Client\AkamaiClientV2
   *   An AkamaiClient to test.
   */
  protected function getClient(array $config = []) {
    // Ensure some sane defaults.
    $config = $config + [
      'version' => 'v3',
      'domain' => [
        'production' => TRUE,
        'staging' => FALSE,
      ],
      'action_v3' => [
        'delete' => TRUE,
        'invalidate' => FALSE,
      ],
      'basepath' => 'http://example.com',
      'timeout' => 300,
      'purge_urls_with_hostname' => FALSE,
    ];
    $logger = $this->prophesize(LoggerInterface::class)->reveal();
    $status_storage = $this->getMockBuilder('Drupal\akamai\StatusStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $edgegridclient = $this->getMockBuilder('Akamai\Open\EdgeGrid\Client')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    // Create stub for response class.
    $response_stub = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()
      ->setMethods(['getStatusCode'])
      ->getMock();
    $response_stub->method('getStatusCode')
      ->willReturn(201);

    // Create stub for the Akamai Client class.
    $akamai_client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV3')
      ->setConstructorArgs([
        [],
        'v3',
        [],
        $edgegridclient,
        $this->getConfigFactoryStub(['akamai.settings' => $config]),
        $logger,
        $status_storage,
        $this->prophesize(MessengerInterface::class)->reveal(),
        $this->prophesize(KeyProviderInterface::class)->reveal(),
      ])
      ->setMethods(['getQueueLength', 'purgeRequest'])
      ->getMock();

    return $akamai_client;
  }

  /**
   * Tests creation of a purge payload body.
   *
   * @covers ::createPurgeBody
   */
  public function testCreatePurgeBody() {
    $urls = ['/node/11'];
    // URL type (default).
    $expected = (object) [
      'objects' => $urls,
    ];
    $akamai_client = $this->getClient();
    $this->assertEquals($expected, $akamai_client->createPurgeBody($urls));
    // URL type (default) with hostname setting enabled.
    $expected = (object) [
      'objects' => $urls,
      'hostname' => 'http://example.com',
    ];
    $akamai_client = $this->getClient(['purge_urls_with_hostname' => TRUE]);
    $this->assertEquals($expected, $akamai_client->createPurgeBody($urls));
  }

}
