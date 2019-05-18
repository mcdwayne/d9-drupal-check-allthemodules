<?php

namespace Drupal\Tests\openstack_queues\Unit\Queue;

use Drupal\openstack_queues\Queue\OpenstackQueue;
use Drupal\Tests\openstack_queues\Unit\OpenstackQueueTestBase;
use Drupal\Tests\UnitTestCase;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use OpenCloud\Rackspace;
use OpenCloud\Tests\MockSubscriber;

/**
 * @coversDefaultClass \Drupal\openstack_queues\Queue\OpenstackQueue
 * @group openstack_queues
 */
class OpenstackQueueTest extends OpenstackQueueTestBase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateItem() {
    $data = 'Do homework';
    $this->assertTrue($this->queue->createItem($data));
  }

  public function testClaimItem() {
    $this->addMockSubscriber($this->makeResponse('[
   {
      "body":"{\"event\":\"BackupStarted\"}",
      "age":239,
      "href":"/v1/queues/demoqueue/messages/51db6f78c508f17ddc924357?claim_id=51db7067821e727dc24df754",
      "ttl":43200
   }
]', 201));
    $this->assertNotFalse($this->queue->claimItem());
  }

  public function testNumberOfItems() {
    $this->assertNotNull($this->queue->numberOfItems());
  }

  public function testReleaseItem() {
    $item = new \stdClass();
    $item->body = 'Do homework';
    $item->item_id = '123';
    $this->assertTrue($this->queue->releaseItem($item));
  }

}
