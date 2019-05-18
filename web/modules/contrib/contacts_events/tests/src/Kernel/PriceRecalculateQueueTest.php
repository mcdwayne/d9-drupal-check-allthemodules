<?php

namespace Drupal\Tests\contacts_events\Kernel;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * Tests queuing for recalculation.
 */
class PriceRecalculateQueueTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'commerce',
    'commerce_checkout',
    'commerce_order',
    'commerce_price',
    'commerce_store',
    'contacts_events',
    'datetime',
    'datetime_range',
    'entity',
    'entity_reference',
    'entity_reference_revisions',
    'field',
    'file',
    'image',
    'inline_entity_form',
    'name',
    'options',
    'profile',
    'state_machine',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('contacts_event');
    $this->installEntitySchema('user');
    $this->installConfig(['commerce_order', 'contacts_events']);
  }

  /**
   * Test responding to an entity update.
   *
   * @param array $new_values
   *   The changes to make to the event. Keys are field names, values are the
   *   new value for the field.
   * @param array $orders
   *   An array of values for orders to create.
   * @param mixed $expected_result
   *   The expected result from PriceCalculator::onEntityUpdate.
   * @param array $expected_jobs
   *   An array of jobs we expect to be queued.
   *
   * @dataProvider dataOnEntityUpdate
   *
   * @covers \Drupal\contacts_events\PriceCalculator::onEntityUpdate
   */
  public function testOnEntityUpdate(array $new_values, array $orders, $expected_result, array $expected_jobs) {
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $event_storage = $entity_type_manager->getStorage('contacts_event');
    $order_storage = $entity_type_manager->getStorage('commerce_order');

    // Create our events.
    $event_storage->create([
      'type' => 'default',
      'id' => 101,
      'title' => 'Other Event 101',
    ])->save();

    $event_storage->create([
      'type' => 'default',
      'id' => 102,
      'title' => 'Other Event 102',
    ])->save();

    $event_storage->create([
      'type' => 'default',
      'id' => 1,
      'title' => 'Test Event',
      'booking_windows' => [
        [
          'id' => 'early',
          'label' => 'Early bird',
          'cut_off' => '2018-10-01',
        ],
        [
          'id' => 'standard',
          'label' => 'Standard',
        ],
      ],
      'ticket_classes' => ['standard'],
      'ticket_price' => [
        [
          'number' => 4.99,
          'currency_code' => 'USD',
          'booking_window' => 'early',
          'class' => 'standard',
        ],
        [
          'number' => 9.99,
          'currency_code' => 'USD',
          'booking_window' => 'standard',
          'class' => 'standard',
        ],
      ],
    ])->save();
    $event = $event_storage->loadUnchanged(1);
    $original_event = $event_storage->loadUnchanged(1);

    // Create our orders.
    $order_storage->create([
      'type' => 'contacts_booking',
      'order_id' => 101,
      'event' => 101,
    ])->save();
    $order_storage->create([
      'type' => 'contacts_booking',
      'order_id' => 102,
      'event' => 102,
    ])->save();
    foreach ($orders as $order) {
      $order_storage->create($order)->save();
    }

    // Make our changes.
    foreach ($new_values as $field => $values) {
      $event->set($field, $values);
    }

    // Mock our entity type manager so we can prophecy the calls on queue.
    $entity_type_manager_mock = $this->prophesize(EntityTypeManagerInterface::class);

    // We want the actual order storage returned.
    $entity_type_manager_mock->getStorage('commerce_order')->willReturn($order_storage);

    // Mock our queue storage and queue.
    if (!empty($expected_jobs)) {
      $queue = $this->prophesize(QueueInterface::class);
      $callback = function (array $jobs) use ($expected_jobs) {
        /* @var \Drupal\commerce_advancedqueue\CommerceOrderJob[] $jobs */
        foreach ($jobs as $job) {
          // Check the job type.
          if ($job->getType() != 'contacts_events_recalculate_order_items') {
            return FALSE;
          }

          // Check the job exists for the order.
          if (!isset($expected_jobs[$job->getOrderId()])) {
            return FALSE;
          }

          // Check the payload matches.
          if ($expected_jobs[$job->getOrderId()] != $job->getPayload()) {
            return FALSE;
          }

          // Remove the expected job to ensure we don't get it twice.
          unset($expected_jobs[$job->getOrderId()]);
        }

        // Ensure we weren't expecting any other jobs.
        return empty($expected_jobs);
      };
      $queue->enqueueJobs(Argument::that($callback))
        ->shouldBeCalledTimes(1);

      $queue_storage = $this->prophesize(EntityStorageInterface::class);
      $queue_storage->load('commerce_order')
        ->shouldBeCalledTimes(1)
        ->willReturn($queue->reveal());

      $entity_type_manager_mock->getStorage('advancedqueue_queue')
        ->shouldBeCalledTimes(1)
        ->willReturn($queue_storage->reveal());
    }
    else {
      $entity_type_manager_mock->getStorage('advancedqueue_queue')
        ->shouldBeCalledTimes(0);
    }

    // Fire our on update method.
    $price_calculator = new PriceCalculator(
      $entity_type_manager_mock->reveal(),
      $this->container->get('logger.channel.contacts_events')
    );
    $this->assertSame($expected_result, $price_calculator->onEntityUpdate($event, $original_event));
  }

  /**
   * Data provider for testOnEntityUpdate.
   */
  public function dataOnEntityUpdate() {
    $data['no-change-no-orders'] = [
      'new_values' => [],
      'orders' => [],
      'expected_result' => FALSE,
      'expected_jobs' => [],
    ];

    $data['no-change-orders'] = [
      'new_values' => [],
      'orders' => [
        [
          'type' => 'contacts_booking',
          'order_id' => 1,
          'event' => 1,
        ],
        [
          'type' => 'contacts_booking',
          'order_id' => 2,
          'event' => 1,
        ],
      ],
      'expected_result' => FALSE,
      'expected_jobs' => [],
    ];

    $data['change-code-no-orders'] = [
      'new_values' => [
        'code' => 'TEST',
      ],
      'orders' => [],
      'expected_result' => FALSE,
      'expected_jobs' => [],
    ];

    $data['change-code-orders'] = [
      'new_values' => [
        'code' => 'TEST',
      ],
      'orders' => [
        [
          'type' => 'contacts_booking',
          'order_id' => 1,
          'event' => 1,
        ],
        [
          'type' => 'contacts_booking',
          'order_id' => 2,
          'event' => 1,
        ],
      ],
      'expected_result' => FALSE,
      'expected_jobs' => [],
    ];

    $data['change-classes-no-orders'] = [
      'new_values' => [
        'ticket_classes' => ['child', 'standard'],
      ],
      'orders' => [],
      'expected_result' => 0,
      'expected_jobs' => [],
    ];

    $data['change-classes-orders'] = [
      'new_values' => [
        'ticket_classes' => ['child', 'standard'],
      ],
      'orders' => [
        [
          'type' => 'contacts_booking',
          'order_id' => 1,
          'event' => 1,
        ],
        [
          'type' => 'contacts_booking',
          'order_id' => 2,
          'event' => 1,
        ],
      ],
      'expected_result' => 2,
      'expected_jobs' => [
        1 => ['bundles' => ['contacts_ticket']],
        2 => ['bundles' => ['contacts_ticket']],
      ],
    ];

    $data['change-windows-no-orders'] = [
      'new_values' => [
        'booking_windows' => [
          [
            'id' => 'early',
            'label' => 'Early bird',
            'cut_off' => '2018-05-01',
          ],
          [
            'id' => 'standard',
            'label' => 'Standard',
          ],
        ],
      ],
      'orders' => [],
      'expected_result' => 0,
      'expected_jobs' => [],
    ];

    $data['change-windows-orders'] = [
      'new_values' => [
        'booking_windows' => [
          [
            'id' => 'early',
            'label' => 'Early bird',
            'cut_off' => '2018-05-01',
          ],
          [
            'id' => 'standard',
            'label' => 'Standard',
          ],
        ],
      ],
      'orders' => [
        [
          'type' => 'contacts_booking',
          'order_id' => 1,
          'event' => 1,
        ],
        [
          'type' => 'contacts_booking',
          'order_id' => 2,
          'event' => 1,
        ],
      ],
      'expected_result' => 2,
      'expected_jobs' => [
        1 => ['bundles' => ['contacts_ticket']],
        2 => ['bundles' => ['contacts_ticket']],
      ],
    ];

    $data['change-price-no-orders'] = [
      'new_values' => [
        'ticket_price' => [
          [
            'number' => 4.99,
            'currency_code' => 'USD',
            'booking_window' => 'early',
            'class' => 'standard',
          ],
          [
            'number' => 14.99,
            'currency_code' => 'USD',
            'booking_window' => 'standard',
            'class' => 'standard',
          ],
        ],
      ],
      'orders' => [],
      'expected_result' => 0,
      'expected_jobs' => [],
    ];

    $data['change-price-orders'] = [
      'new_values' => [
        'ticket_price' => [
          [
            'number' => 4.99,
            'currency_code' => 'USD',
            'booking_window' => 'early',
            'class' => 'standard',
          ],
          [
            'number' => 14.99,
            'currency_code' => 'USD',
            'booking_window' => 'standard',
            'class' => 'standard',
          ],
        ],
      ],
      'orders' => [
        [
          'type' => 'contacts_booking',
          'order_id' => 1,
          'event' => 1,
        ],
        [
          'type' => 'contacts_booking',
          'order_id' => 2,
          'event' => 1,
        ],
      ],
      'expected_result' => 2,
      'expected_jobs' => [
        1 => ['bundles' => ['contacts_ticket']],
        2 => ['bundles' => ['contacts_ticket']],
      ],
    ];

    return $data;
  }

}
