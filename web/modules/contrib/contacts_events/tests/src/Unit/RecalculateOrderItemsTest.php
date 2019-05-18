<?php

namespace Drupal\Tests\contacts_events\Unit;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\commerce_advancedqueue\CommerceOrderJob;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\contacts_events\Plugin\AdvancedQueue\JobType\RecalculateOrderItems;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the order item price recalculator job.
 *
 * @covers \Drupal\contacts_events\Plugin\AdvancedQueue\JobType\RecalculateOrderItems::doProcess
 * @group contacts_events
 */
class RecalculateOrderItemsTest extends UnitTestCase {

  /**
   * Test the processing of a recalculate order item job.
   *
   * @param array $payload
   *   The payload for the job.
   * @param array $order_items
   *   An array of order items, values an array containing:
   *   - 'bundle': The bundle of the order item; NULL or missing if it shouldn't
   *     be checked.
   *   - 'id': The ID of the order item; NULL or missing if it shouldn't be
   *     checked.
   *   - 'calc': Whether the calculation should succeed; NULL or missing if it
   *     shouldn't be attempted.
   *   - 'save': Whether saving should succeed; NULL or missing if it shouldn't
   *     be attempted.
   * @param \Drupal\advancedqueue\JobResult $expected_result
   *   The expected result.
   * @param bool $order_needs_save
   *   Whether the order should be saved.
   *
   * @dataProvider dataDoProcess
   */
  public function testDoProcess(array $payload, array $order_items, JobResult $expected_result, $order_needs_save) {
    // Build our prophecies.
    $order = $this->prophesize(OrderInterface::class);
    $price_calculator = $this->prophesize(PriceCalculator::class);

    // Set up our expectations.
    $order_item_prophecies = [];
    foreach ($order_items as $expected_item) {
      $item = $this->prophesize(OrderItemInterface::class);

      $method = $item->bundle();
      if (isset($expected_item['bundle'])) {
        $method
          ->shouldBeCalledTimes(1)
          ->willReturn($expected_item['bundle']);
      }
      else {
        $method->shouldNotBeCalled();
      }

      $method = $item->id();
      if (isset($expected_item['id'])) {
        $method
          ->shouldBeCalledTimes(1)
          ->willReturn($expected_item['id']);
      }
      else {
        $method->shouldNotBeCalled();
      }

      $method = $price_calculator->calculatePrice($item->reveal());
      if (isset($expected_item['calc'])) {
        $method->shouldBeCalledTimes(1);
        if (!$expected_item['calc']) {
          $method->willThrow(new \Exception('Calc failed'));
        }
      }
      else {
        $method->shouldNotBeCalled();
      }

      $method = $item->save();
      if (isset($expected_item['save'])) {
        $method->shouldBeCalledTimes(1);
        if (!$expected_item['save']) {
          $method->willThrow(new EntityStorageException('Save failed'));
        }
      }
      else {
        $method->shouldNotBeCalled();
      }

      $order_item_prophecies[] = $item;
    }
    $order->getItems()->willReturn($order_item_prophecies);
    $order->save()->shouldNotBeCalled();

    // Create our JobType.
    $container = $this->buildContainerProphecy(5, $order, $expected_result->getState() == Job::STATE_FAILURE);
    $container->get('contacts_events.price_calculator')->willReturn($price_calculator->reveal());
    $job_type = RecalculateOrderItems::create($container->reveal(), [], 'contacts_events_recalculate_order_items', []);

    // Build our job.
    $job = CommerceOrderJob::create('contacts_events_recalculate_order_items', $payload, 5);
    $job->setDeferOrderSave();

    // Process the job.
    $result = $job_type->process($job);

    // Check our result.
    $this->assertEquals($expected_result, $result, 'Expected result');
    $this->assertSame($order_needs_save, $job->orderNeedsSave(), 'Order needs saving');
  }

  /**
   * Data provider for testDoProcess.
   */
  public function dataDoProcess() {
    $data = [];

    $data['invalid-payload'] = [
      ['other' => 'value'],
      [],
      new JobResult(Job::STATE_FAILURE, 'Missing payload', 0),
      FALSE,
    ];

    $data['bundles-no-items'] = [
      ['bundles' => ['contacts_ticket']],
      [],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['items-no-bundles'] = [
      ['bundles' => []],
      [
        [
          'bundle' => 'contacts_ticket',
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['diff-items-bundles'] = [
      ['bundles' => ['contacts_ticket']],
      [
        [
          'bundle' => 'contacts_accommodation',
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['bundles-in-items'] = [
      ['bundles' => ['contacts_ticket']],
      [
        [
          'bundle' => 'contacts_ticket',
          'calc' => TRUE,
          'save' => TRUE,
        ],
        [
          'bundle' => 'contacts_ticket',
          'calc' => TRUE,
          'save' => TRUE,
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      TRUE,
    ];

    $data['ids-no-items'] = [
      ['ids' => [10]],
      [],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['items-no-ids'] = [
      ['ids' => []],
      [
        [
          'id' => 10,
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['diff-items-ids'] = [
      ['ids' => [50]],
      [
        [
          'id' => 10,
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      FALSE,
    ];

    $data['ids-in-items'] = [
      ['ids' => [10, 15]],
      [
        [
          'id' => 10,
          'calc' => TRUE,
          'save' => TRUE,
        ],
        [
          'id' => 15,
          'calc' => TRUE,
          'save' => TRUE,
        ],
      ],
      new JobResult(Job::STATE_SUCCESS),
      TRUE,
    ];

    $data['calc-failed'] = [
      ['ids' => [10]],
      [
        [
          'id' => 10,
          'calc' => FALSE,
        ],
      ],
      new JobResult(Job::STATE_FAILURE, 'Calc failed'),
      FALSE,
    ];

    $data['save-failed'] = [
      ['ids' => [10]],
      [
        [
          'id' => 10,
          'calc' => TRUE,
          'save' => FALSE,
        ],
      ],
      new JobResult(Job::STATE_FAILURE, 'Save failed', 5, 60),
      FALSE,
    ];

    $data['first-failed'] = [
      ['ids' => [10, 15]],
      [
        [
          'id' => 10,
          'calc' => FALSE,
        ],
        [
          // Nothing check on this item.
        ],
      ],
      new JobResult(Job::STATE_FAILURE, 'Calc failed'),
      FALSE,
    ];

    $data['last-failed'] = [
      ['ids' => [10, 15]],
      [
        [
          'id' => 10,
          'calc' => TRUE,
          'save' => TRUE,
        ],
        [
          'id' => 15,
          'calc' => FALSE,
        ],
      ],
      new JobResult(Job::STATE_FAILURE, 'Calc failed'),
      FALSE,
    ];

    return $data;
  }

  /**
   * Build the container object prophecy.
   *
   * @param int $order_id
   *   The order ID that will be retrieved.
   * @param \Prophecy\Prophecy\ObjectProphecy $order
   *   The prophecy of the order to return.
   * @param bool $transaction_rollback
   *   Whether the transaction will be rolled back.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The prophecy for the container.
   */
  protected function buildContainerProphecy($order_id, ObjectProphecy $order, $transaction_rollback = FALSE) {
    $order_storage = $this->prophesize(EntityStorageInterface::class);
    $order_storage->load($order_id)->willReturn($order->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('commerce_order')->willReturn($order_storage->reveal());

    $transaction = $this->prophesize(Transaction::class);
    $transaction->rollBack()->shouldBeCalledTimes($transaction_rollback ? 1 : 0);

    $database = $this->prophesize(Connection::class);
    $database->startTransaction()->willReturn($transaction->reveal());

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity_type.manager')->willReturn($entity_type_manager->reveal());
    $container->get('database')->willReturn($database->reveal());

    return $container;
  }

}
