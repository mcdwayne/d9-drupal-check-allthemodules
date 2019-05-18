<?php

namespace Drupal\Tests\contacts_events\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\contacts_events\Entity\Event;
use Drupal\contacts_events\Entity\EventClass;
use Drupal\contacts_events\Entity\Ticket;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the event class evaluation.
 *
 * @coversDefaultClass \Drupal\contacts_events\Entity\EventClass
 *
 * @group Contacts
 */
class EventClassEvaluationTest extends KernelTestBase {

  use EventClassConditionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $fullStackModules = [
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
    'rules',
    'state_machine',
    'system',
    'typed_data',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test that evaluate throws an exception without the order item context.
   */
  public function testNoOrderItemContext() {
    // Create a class.
    $class = new EventClass([
      'id' => 'standard',
      'type' => 'global',
      'expression' => NULL,
    ], 'event_class');

    // Attempt execution with no context.
    $contexts = [];
    $this->setExpectedException(\InvalidArgumentException::class, 'Cannot evaluate an event class without an order item.');
    $this->assertTrue($class->evaluate($contexts));
  }

  /**
   * Test classes with no conditions, ensuring types are protected.
   *
   * @param string $class_type
   *   The class type.
   * @param string $order_item_type
   *   The order item type.
   * @param bool $expected
   *   The expected result of evaluation.
   *
   * @dataProvider dataGlobalNoConditions
   */
  public function testNoConditions($class_type, $order_item_type, $expected) {
    // Create a class.
    $class = new EventClass([
      'id' => 'standard',
      'type' => $class_type,
      'expression' => NULL,
    ], 'event_class');

    // Set up our context.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->bundle()->willReturn($order_item_type);
    $contexts = ['order_item' => $order_item->reveal()];

    $this->assertEquals($expected, $class->evaluate($contexts, TRUE));
  }

  /**
   * Data provider for testNoConditions.
   */
  public function dataGlobalNoConditions() {
    // Global event class - ticket order item.
    $data['global-contacts_ticket'] = [
      'class_type' => 'global',
      'order_item_type' => 'contacts_ticket',
      'expected' => TRUE,
    ];

    // Ticket event class - ticket order item.
    $data['contacts_ticket-contacts_ticket'] = [
      'class_type' => 'contacts_ticket',
      'order_item_type' => 'contacts_ticket',
      'expected' => TRUE,
    ];

    // Other event class - ticket order item.
    $data['other-contacts_ticket'] = [
      'class_type' => 'other',
      'order_item_type' => 'contacts_ticket',
      'expected' => FALSE,
    ];

    // Global event class - other order item.
    $data['global-other'] = [
      'class_type' => 'global',
      'order_item_type' => 'other',
      'expected' => TRUE,
    ];

    // Ticket event class - other order item.
    $data['contacts_ticket-other'] = [
      'class_type' => 'contacts_ticket',
      'order_item_type' => 'other',
      'expected' => FALSE,
    ];

    // Other event class - other order item.
    $data['other-other'] = [
      'class_type' => 'other',
      'order_item_type' => 'other',
      'expected' => TRUE,
    ];

    return $data;
  }

  /**
   * Test classes with date conditions.
   *
   * @param string $date_of_birth
   *   The date of birth on the ticket.
   * @param string $event_date
   *   The date of the event.
   * @param bool $adult_result
   *   The expected result from evaluating the adult class.
   * @param bool $child_result
   *   The expected result from evaluating the child class.
   *
   * @dataProvider dataTestDateCondition
   */
  public function testDateConditions($date_of_birth, $event_date, $adult_result, $child_result) {
    $this->enableModules(static::$fullStackModules);
    $this->installConfig(['commerce_order', 'contacts_events']);
    $this->installEntitySchema('contacts_event');
    $this->installEntitySchema('contacts_ticket');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');

    // Set up our context.
    $event = Event::create([
      'type' => 'default',
      'date' => ['value' => $event_date],
    ]);

    $order = Order::create([
      'type' => 'contacts_booking',
      'event' => $event,
    ]);

    $order_item = OrderItem::create([
      'type' => 'contacts_ticket',
      'order_id' => $order,
    ]);

    $ticket = Ticket::create([
      'type' => 'standard',
      'date_of_birth' => $date_of_birth,
      'order_item' => $order_item,
      'event' => $event,
    ]);

    $order_item->set('purchased_entity', $ticket);

    $contexts = ['order_item' => $order_item];

    // Create and evaluate our adult class.
    $adult_class = new EventClass([
      'id' => 'adult',
      'type' => 'contacts_ticket',
    ], 'event_class');
    $this->addClassDateCondition($adult_class, 'P18Y', NULL);
    $this->assertEquals($adult_result, $adult_class->evaluate($contexts), 'Adult evaluation');

    // Create and evaluate our child class.
    $child_class = new EventClass([
      'id' => 'child',
      'type' => 'contacts_ticket',
    ], 'event_class');
    $this->addClassDateCondition($child_class, NULL, 'P18Y');
    $this->assertEquals($child_result, $child_class->evaluate($contexts), 'Child evaluation');
  }

  /**
   * Data provider for testDateConditions.
   */
  public function dataTestDateCondition() {
    $data['over_18'] = [
      'date_of_birth' => '2000-01-01',
      'event_date' => '2018-08-01T10:00:00',
      'adult_result' => TRUE,
      'child_result' => FALSE,
    ];
    $data['exactly_18'] = [
      'date_of_birth' => '2000-08-01',
      'event_date' => '2018-08-01T10:00:00',
      'adult_result' => TRUE,
      'child_result' => FALSE,
    ];
    $data['almost_18'] = [
      'date_of_birth' => '2000-08-02',
      'event_date' => '2018-08-01T10:00:00',
      'adult_result' => FALSE,
      'child_result' => TRUE,
    ];
    $data['under_18'] = [
      'date_of_birth' => '2001-01-01',
      'event_date' => '2018-08-01T10:00:00',
      'adult_result' => FALSE,
      'child_result' => TRUE,
    ];
    return $data;
  }

}
