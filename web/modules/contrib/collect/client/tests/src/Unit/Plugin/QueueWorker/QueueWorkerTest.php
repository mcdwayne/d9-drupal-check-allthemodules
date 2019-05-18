<?php
/**
 * @file
 * Contains \Drupal\Tests\collect_client\Unit\Plugin\QueueWorker\QueueWorkerTest.
 */

namespace Drupal\Tests\collect_client\Unit\Plugin\QueueWorker;

use Drupal\collect_client\CollectItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the queue worker of the collect client.
 *
 * @group collect
 */
class QueueWorkerTest extends UnitTestCase {

  /**
   * The plugin manager for the queue item handle plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The mocked http client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpClient;

  /**
   * The mocked serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * The mocked config.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $config;

  /**
   * The mocked HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * The cache interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->pluginManager = $this->getMock('Drupal\Component\Plugin\PluginManagerInterface');
    $this->logger = $this->getMock('Drupal\Core\Logger\LoggerChannelInterface');
    $this->httpClient = $this->getMock('GuzzleHttp\Client', ['post']);
    $this->serializer = $this->getMock('\Symfony\Component\Serializer\SerializerInterface');
    $this->config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $this->request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
    $this->cache = $this->getMock('\Drupal\Core\Cache\CacheBackendInterface');
  }

  /**
   * Mocks the CollectClientQueueWorker class.
   *
   * @param array|null $methods
   *   (optional) Methods to mock.
   *
   * @return \Drupal\collect_client\Plugin\QueueWorker\CollectClientQueueWorker|\PHPUnit_Framework_MockObject_MockObject
   *   Mocked queue worker object.
   */
  protected function mockQueueWorker($methods = NULL) {
    return $this->getMockBuilder('Drupal\collect_client\Plugin\QueueWorker\CollectClientQueueWorker')
      ->setConstructorArgs([array(), 'test', array(), $this->pluginManager, $this->logger, $this->httpClient, $this->serializer, $this->config, $this->request, $this->cache])
      ->setMethods($methods)
      ->getMock();
  }

  /**
   * Tests the discovery, instantiation and sorting of plugins.
   */
  public function testGetPluginInstances() {

    $definitions = array(
      'a' => array('weight' => 5),
      'b' => array('weight' => 11),
      'c' => array('weight' => -1),
    );
    $instances = array(
      'a' => $this->getMock('Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface'),
      'b' => $this->getMock('Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface'),
      'c' => $this->getMock('Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface'),
    );

    $this->pluginManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->pluginManager->expects($this->exactly(3))
      ->method('createInstance')
      ->will($this->returnValueMap(array(
        array('a', $definitions['a'], $instances['a']),
        array('b', $definitions['b'], $instances['b']),
        array('c', $definitions['c'], $instances['c']),
      )));

    $returned_instances = $this->mockQueueWorker()->getPluginInstances();

    $this->assertTrue(is_array($returned_instances));
    $this->assertEquals(3, count($returned_instances));
    $this->assertEquals($instances['b'], array_shift($returned_instances));
    $this->assertEquals($instances['a'], array_shift($returned_instances));
    $this->assertEquals($instances['c'], array_shift($returned_instances));
  }

  /**
   * Tests the lookup for a supporting plugin.
   */
  public function testGetSupportingPluginInstance() {
    $plugin_instance = $this->getMock('Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface');

    $queue_worker = $this->mockQueueWorker(['getPluginInstances']);
    $queue_worker->expects($this->any())
      ->method('getPluginInstances')
      ->willReturn(array($plugin_instance));

    $plugin_instance->expects($this->any())
      ->method('supports')
      ->will($this->returnValueMap(array(
        array('foo', FALSE),
        array('bar', TRUE),
      )));

    $this->assertNull($queue_worker->getSupportingPluginInstance('foo'));
    $this->assertEquals($plugin_instance, $queue_worker->getSupportingPluginInstance('bar'));
  }

  /**
   * Test the handling when no plugin supports the item.
   *
   * @expectedException \Exception
   *
   * @expectedExceptionMessage No handler defined supporting the queued item.
   */
  public function testHandleWithoutSupportingPlugin() {
    $queue_worker = $this->mockQueueWorker(['getPluginInstances']);
    $queue_worker->expects($this->any())
      ->method('getPluginInstances')
      ->willReturn(array());
    $queue_worker->processItem('foo');
  }

  /**
   * Tests the handling when a plugin supports the item.
   */
  public function testHandleWithSupportingPlugin() {
    $plugin_instance = $this->getMock('Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface');

    $queue_worker = $this->mockQueueWorker(['getPluginInstances']);
    $queue_worker->expects($this->any())
      ->method('getPluginInstances')
      ->willReturn(array($plugin_instance));

    $plugin_instance->expects($this->once())
      ->method('supports')
      ->with('foo')
      ->will($this->returnValue(TRUE));

    $item = new CollectItem();

    $plugin_instance->expects($this->once())
      ->method('handle')
      ->with('foo')
      ->will($this->returnValue($item));

    $data =<<<EOF
{
  "origin_uri": "http://drupal.org/project/collect/test/foo",
  "schema_uri": "http://drupal.org/project/collect/test/bar",
  "data": "foo",
  "type": "text/plain"
}
EOF;

    $item->data = $data;

    $this->serializer->expects($this->any())
      ->method('serialize')
      ->with($item)
      ->will($this->returnValue($data));
    $options = array(
      'headers' => array(
        'Content-Type' => 'application/json',
      ),
      'body' => $data,
      'auth' => array('api_user', 'secret'),
    );
    $url = 'http://example.com/collect/api/v1/submissions';
    $location = 'http://server.collect.dev/collect/api/v1/submissions/f67c88c9-1608-4f47-8291-4e35411af61f';
    $response = $this->getMock('Psr\Http\Message\ResponseInterface');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->with($url, $options)
      ->will($this->returnValue($response));

    $response->expects($this->once())
      ->method('getStatusCode')
      ->will($this->returnValue(201));

    $response->expects($this->once())
      ->method('getHeader')
      ->with('Location')
      ->will($this->returnValue($location));

    $this->logger->expects($this->once())
      ->method('info')
      ->with('Successfully sent submission to {url}. The record was stored as {location}.', array(
        'url' => $url,
        'location' => $location,
      ));

    $this->config->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(array(
        array('service.url', $url),
        array('service.user', 'api_user'),
        array('service.password', 'secret'),
      )));

    $queue_worker->processItem('foo');
  }
}
