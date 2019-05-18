<?php

namespace Drupal\Tests\contacts_dbs\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\contacts_dbs\Entity\DBSWorkforce;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the status archive queue worker.
 *
 * @coversDefaultClass \Drupal\contacts_dbs\Plugin\QueueWorker\StatusArchiveWorker
 * @group contacts_dbs
 */
class StatusArchiveTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'contacts_dbs',
    'datetime',
    'field',
    'options',
    'user',
    'system',
  ];

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * The DBS queue worker.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('dbs_status');
    $this->installSchema('system', 'sequences');

    $this->cron = $this->container->get('cron');
    $this->queue = $this->container->get('queue')->get('contacts_dbs_archive');

    DBSWorkforce::create([
      'id' => 'default',
      'valid' => 1,
      'alternatives' => [],
    ])->save();
  }

  /**
   * Test the queueing and processing of dbs status expiry.
   *
   * @param array $statuses
   *   The data for DBS Status entities to be created.
   * @param int|null $now
   *   The current time.
   * @param int|null $last_run
   *   The last cron run time.
   * @param int|null $queued_items
   *   The number of items expected in the queue.
   *
   * @dataProvider dataQueueExpired
   */
  public function testQueueExpired(array $statuses, $now, $last_run = NULL, $queued_items = NULL) {
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $status_storage = $entity_type_manager->getStorage('dbs_status');

    // Create our events.
    foreach ($statuses as $status) {
      $status_storage->create([
        'workforce' => 'default',
        'status' => $status['status'],
        'expiry_override' => $status['expiry'],
      ])->save();
    }

    $state = $this->prophesize(StateInterface::class);
    $state->get('contacts_dbs.cron_last_run')->willReturn($last_run);
    $state->set('contacts_dbs.cron_last_run', $now);

    if (is_null($last_run) || strtotime($now) > strtotime($last_run) + 86400) {
      $calls = 1;
      if ($queued_items) {
        $state->set('system.cron_last', $now)->shouldBeCalledOnce();
        $calls++;
      }
      $state->set('contacts_dbs.cron_last_run', $now)->shouldBeCalled($calls);
    }
    else {
      $state->set('contacts_dbs.cron_last_run')->shouldNotBeCalled();
    }

    $this->container->set('state', $state->reveal());

    $time = $this->prophesize(Time::class);
    $time->getRequestTime()->willReturn($now);
    $this->container->set('datetime.time', $time->reveal());

    static::assertEquals(0, $this->queue->numberOfItems());
    contacts_dbs_cron();

    if ($queued_items) {
      // Check number of queued statuses.
      $item = $this->queue->claimItem();
      static::assertEquals($queued_items, count($item->data));

      // Run the queue worker.
      $this->queue->releaseItem($item);
      $this->cron->run();

      foreach ($status_storage->loadMultiple($item->data) as $status) {
        static::assertEquals('dbs_expired', $status->get('status')->value);
      }
    }

    static::assertEquals(0, $this->queue->numberOfItems());
  }

  /**
   * Data provider for testQueueExpired.
   */
  public function dataQueueExpired() {
    $data['no-expired-time'] = [
      'statuses' => [
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-05-31',
        ],
      ],
      'now' => strtotime('2018-04-01'),
    ];

    $data['no-expired-status'] = [
      'statuses' => [
        [
          'status' => 'letter_required',
          'expiry' => '2018-04-01',
        ],
      ],
      'now' => strtotime('2018-04-10'),
    ];

    $data['simple-expired'] = [
      'statuses' => [
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-04-01',
        ],
      ],
      'now' => strtotime('2018-04-10'),
      'last_run' => NULL,
      'queued_items' => 1,
    ];

    $data['multiple-expired'] = [
      'statuses' => [
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-04-01',
        ],
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-04-01',
        ],
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-04-01',
        ],
        [
          'status' => 'letter_required',
          'expiry' => '2018-04-09',
        ],
        [
          'status' => 'disclosure_accepted',
          'expiry' => '2018-04-09',
        ],
      ],
      'now' => strtotime('2018-04-10'),
      'last_run' => NULL,
      'queued_items' => 4,
    ];

    $data['no-run'] = [
      'statuses' => [
        [
          'status' => 'dbs_clear',
          'expiry' => '2018-04-01',
        ],
      ],
      'now' => strtotime('2018-04-10'),
      'last_run' => strtotime('2018-04-10'),
    ];

    return $data;
  }

}
