<?php

namespace Drupal\Tests\dropshark\Functional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\State\StateInterface;
use Drupal\dropshark\Queue\DbQueue;
use Drupal\dropshark\Request\RequestInterface;
use Drupal\Tests\BrowserTestBase;
use Prophecy\Argument;

/**
 * Tests functionality of the database queue.
 *
 * @group dropshark
 */
class DbQueueTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dropshark'];

  /**
   * Tests functionality of the queue.
   */
  public function testQueue() {
    $siteId = 'abc-123';

    /** @var \Drupal\Core\Database\Connection $db */
    $db = $this->container->get('database');

    $request = $this->prophesize(RequestInterface::class);
    $response = new \stdClass();
    $response->code = 200;
    $request->postData(Argument::any())->willReturn($response);

    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('queue.lock_max')->willReturn(300);
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $configFactory->get('dropshark.settings')->willReturn($config->reveal());

    $state = $this->prophesize(StateInterface::class);
    $state->get('dropshark.site_id')->willReturn($siteId);

    $q = new DbQueue($db, $request->reveal(), $configFactory->reveal(), $state->reveal());

    $countQuery = 'SELECT COUNT(*) FROM {dropshark_queue}';
    $lockQuery = 'UPDATE {dropshark_queue} SET lock_id = ? , lock_time = ? LIMIT 1';

    // Add items to queue.
    $q->add(['data_1']);
    $q->add(['data_2']);
    $q->add(['data_3']);

    // Check that the added data was not yet persistently stored.
    $val = $db->query($countQuery)->fetchField();
    $this->assertEquals(0, $val, 'Queue empty.');

    // Make sure our items made it into the persistent storage.
    $q->persist();

    $val = $db->query($countQuery)->fetchField();
    $this->assertEquals(3, $val, '3 items in queue.');

    // Lock one item and process the queue.
    $db->query($lockQuery, [__FUNCTION__, time() - 295]);
    $q->transmit();

    // Verify locked item remains, others processed.
    $val = $db->query($countQuery)->fetchField();
    $this->assertEquals(1, $val, '1 item remaining in queue.');

    // Expire the remaining lock.
    $db->query($lockQuery, [__FUNCTION__, time() - 305]);
    $q->transmit();

    // Verify lock expires.
    $val = $db->query($countQuery)->fetchField();
    $this->assertEquals(0, $val, 'Queue empty.');
  }

}
