<?php

namespace Drupal\Tests\openstack_queues\Unit;

use Drupal\Tests\UnitTestCase;
use Guzzle\Http\Message\Response;
use OpenCloud\Tests\MockSubscriber;
use OpenCloud\Rackspace;
use Drupal\openstack_queues\Queue\OpenstackQueue;

class OpenstackQueueTestBase extends UnitTestCase {

  /**
   * @var Rackspace $client
   */
  protected $client;
  protected $config;
  protected $config_factory;
  protected $queue;

  protected function setUp() {
    parent:: setUp();
    $this->config_factory = $this->getConfigFactoryStub([
      'openstack_queues.settings.default' => [
        'client_id' => '9bcc7ac3-3754-467e-96f1-b51c9168ed3c',
        'auth_url' => 'https://identity.api.rackspacecloud.com/v2.0/',
        'region' => 'DFW',
        'prefix' => '',
        'credentials' => [
          'username' => 'bender',
          'apiKey' => 'rodriguez',
        ],
      ],
    ]);
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->method('get')->with('config.factory')->will($this->returnValue($this->config_factory));
    \Drupal::setContainer($container);

    $this->config = \Drupal::config('openstack_queues.settings.default');
    $this->client = new Rackspace($this->config->get('auth_url'), $this->config->get('credentials'));
    $this->client->addSubscriber(new MockSubscriber());

    $this->queue = new OpenstackQueue('foo', $this->client, $this->config);
  }

  protected function addMockSubscriber($response) {
    $subscriber = new MockSubscriber(array($response), true);
    $this->client->addSubscriber($subscriber);
  }

  protected function makeResponse($body = null, $status = 200) {
    return new Response($status, array('Content-Type' => 'application/json'), $body);
  }

}