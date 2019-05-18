<?php

namespace Drupal\Tests\akamai\Unit;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal\akamai\AkamaiAuthentication;
use Drupal\akamai\KeyProviderInterface;

/**
 * @coversDefaultClass \Drupal\akamai\Plugin\Client\AkamaiClientV2
 *
 * @group Akamai
 */
class AkamaiClientTest extends UnitTestCase {

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
      'version' => 'v2',
      'domain' => [
        'production' => TRUE,
        'staging' => FALSE,
      ],
      'action_v2' => [
        'remove' => TRUE,
        'invalidate' => FALSE,
      ],
      'basepath' => 'http://example.com',
      'timeout' => 300,
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
    $akamai_client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV2')
      ->setConstructorArgs([
        [],
        'v2',
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
    $akamai_client->method('getQueueLength')
      ->willReturn(4);
    $akamai_client->method('purgeRequest')
      ->with(['http://example.com/node/11'])
      ->willReturn($response_stub);

    return $akamai_client;
  }

  /**
   * Tests the setting of a queue.
   *
   * @covers ::setQueue
   */
  public function testSetQueue() {
    $akamai_client = $this->getClient();
    $akamai_client->setQueue('test_queue');
    $this->assertAttributeEquals('test_queue', 'queue', $akamai_client);
  }

  /**
   * Tests the setting of a asset type to clear.
   *
   * @covers ::setType
   */
  public function testSetType() {
    $akamai_client = $this->getClient();
    $akamai_client->setType('cpcode');
    $this->assertAttributeEquals('cpcode', 'type', $akamai_client);
  }

  /**
   * Tests exception on incorrect asset type set.
   *
   * @covers ::setType
   */
  public function testSetTypeException() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Type must be one of: cpcode, arl');
    $akamai_client = $this->getClient();
    $akamai_client->setType('wrong');
    $this->assertAttributeEquals('arl', 'type', $akamai_client);
  }

  /**
   * Tests setting of a purge action type.
   *
   * @covers ::setAction
   */
  public function testSetAction() {
    $akamai_client = $this->getClient();
    $akamai_client->setAction('invalidate');
    $this->assertAttributeEquals('invalidate', 'action', $akamai_client);
  }

  /**
   * Tests exception on incorrect action type set.
   *
   * @covers ::setAction
   */
  public function testSetActionException() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Action must be one of: remove, invalidate');
    $akamai_client = $this->getClient();
    $akamai_client->setAction('wrong');
    $this->assertAttributeEquals('production', 'action', $akamai_client);
  }

  /**
   * Tests setting of a clearing domain.
   *
   * @covers ::setDomain
   */
  public function testSetDomain() {
    $akamai_client = $this->getClient();
    $akamai_client->setDomain('staging');
    $this->assertAttributeEquals('staging', 'domain', $akamai_client);
  }

  /**
   * Tests exception when setting invalid domain.
   *
   * @covers ::setDomain
   */
  public function testSetDomainException() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Domain must be one of: staging, production');
    $akamai_client = $this->getClient();
    $akamai_client->setDomain('wrong');
    $this->assertAttributeEquals('production', 'domain', $akamai_client);
  }

  /**
   * Tests creation of client config.
   *
   * @covers ::createClientConfig
   */
  public function testCreateClientConfig() {
    $client_config = ['rest_api_url' => 'http://example.com'];
    $akamai_client = $this->getClient($client_config);
    $this->assertEquals(['base_uri' => 'http://example.com', 'timeout' => 300], $akamai_client->createClientConfig());

    $client_config = ['rest_api_url' => 'http://example.com'] + $this->getEdgeRcConfig();
    $config_factory = $this->getConfigFactoryStub(['akamai.settings' => $client_config]);
    $auth = AkamaiAuthentication::create(
      $config_factory,
      $this->prophesize(MessengerInterface::class)->reveal(),
      $this->prophesize(KeyProviderInterface::class)->reveal()
    );
    $akamai_client = $this->getClient($client_config);
    $this->assertEquals(['base_uri' => 'edgerc-test.com', 'timeout' => 300], $akamai_client->createClientConfig($auth));
  }

  /**
   * Tests creation of a purge payload body.
   */
  public function testCreatePurgeBody() {
    $urls = ['example.com/node/11'];
    $expected = [
      'action' => 'remove',
      'domain' => 'production',
      'type' => 'arl',
      'objects' => $urls,
    ];
    $akamai_client = $this->getClient();

    $this->assertEquals($expected, $akamai_client->createPurgeBody($urls));
  }

  /**
   * Tests creation of a purge request.
   *
   * @covers ::purgeUrl
   * @covers ::purgeUrls
   * @covers ::purgeRequest
   * @covers ::formatExceptionMessage
   */
  public function testPurgeRequest() {
    $urls = ['http://example.com/node/11'];
    $akamai_client = $this->getClient();

    $response = $akamai_client->purgeUrl($urls[0]);
    $this->assertEquals('GuzzleHttp\Psr7\Response', get_parent_class($response));
    $this->assertEquals('201', $response->getStatusCode());
    $response = $akamai_client->purgeUrls($urls);
    $this->assertEquals('GuzzleHttp\Psr7\Response', get_parent_class($response));
    $this->assertEquals('201', $response->getStatusCode());
  }

  /**
   * Tests checking of a queue.
   *
   * @covers ::getQueue
   * @covers ::getQueueLength
   * @covers ::doGetQueue
   */
  public function testCheckQueue() {
    $akamai_client = $this->getClient();
    $this->assertEquals(4, $akamai_client->getQueueLength());
  }

  /**
   * Tests authorization check.
   *
   * @covers ::isAuthorized
   * @covers ::setApiBaseUrl
   * @covers ::formatExceptionMessage
   */
  public function testIsAuthorized() {
    // Ensure some sane defaults.
    $config = [
      'version' => 'v2',
      'domain' => [
        'production' => TRUE,
        'staging' => FALSE,
      ],
      'action_v2' => [
        'remove' => TRUE,
        'invalidate' => FALSE,
      ],
      'basepath' => 'http://example.com',
      'timeout' => 300,
    ];
    $logger = $this->prophesize(LoggerInterface::class)->reveal();
    $status_storage = $this->getMockBuilder('Drupal\akamai\StatusStorage')
      ->disableOriginalConstructor()
      ->getMock();

    // Create stub for 200 response class.
    $response_stub = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response_stub->method('getStatusCode')
      ->willReturn(200);
    $edgegridclient = $this->getMockBuilder('Akamai\Open\EdgeGrid\Client')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();
    $edgegridclient->expects($this->at(0))
      ->method('get')
      ->with('/ccu/v2/queues/default')
      ->willReturn($response_stub);

    // Create stub for 404 response class.
    $response_stub = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response_stub->method('getStatusCode')
      ->willReturn(404);
    $edgegridclient->expects($this->at(1))
      ->method('get')
      ->with($this->logicalNot($this->equalTo('/ccu/v2/queues/default')))
      ->willReturn($response_stub);

    // Create stub for the Akamai Client class.
    $akamai_client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV2')
      ->setConstructorArgs([
        [],
        'v2',
        [],
        $edgegridclient,
        $this->getConfigFactoryStub(['akamai.settings' => $config]),
        $logger,
        $status_storage,
        $this->prophesize(MessengerInterface::class)->reveal(),
        $this->prophesize(KeyProviderInterface::class)->reveal(),
      ])
      ->setMethods(NULL)
      ->getMock();

    // Test isAuthorized.
    $this->assertTrue($akamai_client->isAuthorized());

    // Intentionally send a bad request.
    $akamai_client->setApiBaseUrl('not-a-url');
    $this->assertFalse($akamai_client->isAuthorized());
  }

  /**
   * Tests checking of purge status.
   *
   * @covers ::getPurgeStatus
   */
  public function testGetPurgeStatus() {
    // Ensure some sane defaults.
    $config = [
      'version' => 'v2',
      'domain' => [
        'production' => TRUE,
        'staging' => FALSE,
      ],
      'action_v2' => [
        'remove' => TRUE,
        'invalidate' => FALSE,
      ],
      'basepath' => 'http://example.com',
      'timeout' => 300,
    ];
    $logger = $this->prophesize(LoggerInterface::class)->reveal();
    $status_storage = $this->getMockBuilder('Drupal\akamai\StatusStorage')
      ->disableOriginalConstructor()
      ->getMock();

    // Create stub for response class.
    $response_stub = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response_stub->method('getStatusCode')
      ->willReturn(200);

    // Create mock for the Akamai client class.
    $edgegridclient = $this->getMockBuilder('Akamai\Open\EdgeGrid\Client')
      ->disableOriginalConstructor()
      ->setMethods(['request'])
      ->getMock();
    $edgegridclient->expects($this->at(0))
      ->method('request')
      ->with('GET', $this->callback(function ($payload) {
        return $payload === '/ccu/v2/purges/dummy_id';
      }))
      ->willReturn($response_stub);
    $edgegridclient->expects($this->at(1))
      ->method('request')
      ->with($this->equalTo('GET'), $this->callback(function ($payload) {
        return $payload !== '/ccu/v2/purges/dummy_id';
      }))
      ->willReturn(FALSE);
    // Create stub for the Akamai Client class.
    $akamai_client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV2')
      ->setConstructorArgs([
        [],
        'v2',
        [],
        $edgegridclient,
        $this->getConfigFactoryStub(['akamai.settings' => $config]),
        $logger,
        $status_storage,
        $this->prophesize(MessengerInterface::class)->reveal(),
        $this->prophesize(KeyProviderInterface::class)->reveal(),
      ])
      ->setMethods(NULL)
      ->getMock();

    // Test purge status/ get status code.
    $response = $akamai_client->getPurgeStatus('dummy_id');
    $this->assertEquals('GuzzleHttp\Psr7\Response', get_parent_class($response));
    $this->assertEquals('200', $response->getStatusCode());

    // Intentionally send bad request.
    $akamai_client->setApiBaseUrl('not-a-url');
    $this->assertFalse($akamai_client->getPurgeStatus('dummy_id'));
  }

  /**
   * Tests that a URL contains the Akamai managed domain.
   *
   * @covers ::isAkamaiManagedUrl
   * @covers ::setBaseUrl
   */
  public function testIsAkamaiManagedUrl() {
    $akamai_client = $this->getClient();
    $akamai_client->setBaseUrl('http://example2.com/');
    $this->assertTrue($akamai_client->isAkamaiManagedUrl('http://example2.com/example/url'));
  }

  /**
   * Tests that a URL is converted to fully qualified as appropriate.
   *
   * @covers ::normalizeUrl
   */
  public function testNormalizeUrl() {
    $akamai_client = $this->getClient();
    $this->assertEquals('http://example3.com/my/url', $akamai_client->normalizeUrl('http://example3.com/my/url'));
    // Using example.com from the client config.
    $this->assertEquals('http://example.com/my/url', $akamai_client->normalizeUrl('my/url'));
  }

  /**
   * Tests that a group of URLs are converted to fully qualified as appropriate.
   *
   * @covers ::normalizeUrls
   */
  public function testNormalizeUrls() {
    $akamai_client = $this->getClient();

    $input = [
      'node/11',
      'http://example.com/node/13',
      'my/great/page',
    ];

    // Using example.com from the client config.
    $expected = [
      'http://example.com/node/11',
      'http://example.com/node/13',
      'http://example.com/my/great/page',
    ];

    $this->assertEquals($expected, $akamai_client->normalizeUrls($input));
  }

  /**
   * Returns config for edge rc authentication mode.
   *
   * @return array
   *   An array of config values.
   */
  protected function getEdgeRcConfig() {
    return [
      'storage_method' => 'file',
      'edgerc_path' => realpath(__DIR__ . '/fixtures/.edgerc'),
      'edgerc_section' => 'default',
    ];
  }

}
