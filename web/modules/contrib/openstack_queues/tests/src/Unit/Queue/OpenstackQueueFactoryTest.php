<?php

namespace Drupal\Tests\openstack_queues\Unit\Queue;
use Drupal\openstack_queues\Queue\OpenstackQueueFactory;
use Drupal\Tests\openstack_queues\Unit\OpenstackQueueTestBase;

/**
 * @coversDefaultClass \Drupal\openstack_queues\Queue\OpenstackQueueFactory
 * @group openstack_queues
 */
class OpenstackQueueFactoryTest extends OpenstackQueueTestBase {

  protected $factory;

  protected function setUp() {
    parent::setUp();
    $this->factory = $this->getMock('\Drupal\openstack_queues\Queue\OpenstackQueueFactory', array('get'), array($this->config_factory));
    $this->factory->method('get')->with('default')->willReturn($this->queue);
  }

  public function testFactory() {
    $this->assertInstanceOf('\Drupal\openstack_queues\Queue\OpenstackQueue', $this->factory->get('default'));
  }

}