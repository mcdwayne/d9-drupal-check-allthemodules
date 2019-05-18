<?php

namespace Drupal\Tests\contacts_events\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\contacts_events\Cron\RecalculateOnBookingWindow;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * Test the booking window recalculation cron task.
 *
 * @coversDefaultClass \Drupal\contacts_events\Cron\RecalculateOnBookingWindow
 */
class RecalculateOnBookingWindowTest extends KernelTestBase {

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
    $this->installEntitySchema('contacts_event');
    $this->installConfig(['commerce_order', 'contacts_events']);
  }

  /**
   * Test the booking window recalculation.
   *
   * @param array $booking_windows
   *   The booking window cut off, keyed by event ID.
   * @param int|null $now
   *   The current time.
   * @param int|null $last_run
   *   The last cron run time.
   * @param array|null $event_ids
   *   The event IDs to queue, or NULL for none.
   *
   * @dataProvider dataDoInvoke
   *
   * @covers ::doInvoke
   */
  public function testDoInvoke(array $booking_windows, $now, $last_run, $event_ids) {
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $event_storage = $entity_type_manager->getStorage('contacts_event');

    // Create our events.
    foreach ($booking_windows as $event_id => $cut_off) {
      $event_storage->create([
        'type' => 'default',
        'id' => $event_id,
        'booking_windows' => [
          [
            'id' => 'early',
            'label' => 'Early bird',
            'cut_off' => $cut_off,
          ],
          [
            'id' => 'standard',
            'label' => 'Standard',
          ],
        ],
      ])->save();
    }

    $state = $this->prophesize(StateInterface::class);
    $state->get(RecalculateOnBookingWindow::STATE_LAST_RUN)->wilLReturn($last_run);
    $state->set(RecalculateOnBookingWindow::STATE_LAST_RUN, $now);

    $time = $this->prophesize(Time::class);
    $time->getCurrentTime()->willReturn($now);

    $price_calculator = $this->prophesize(PriceCalculator::class);
    if ($event_ids === NULL) {
      $price_calculator
        ->enqueueJobs(Argument::any(), Argument::any())
        ->shouldNotBeCalled();
    }
    else {
      $price_calculator
        ->enqueueJobs($event_ids, ['contacts_ticket'])
        ->shouldBeCalledTimes(1);
    }

    $cron = new RecalculateOnBookingWindow($state->reveal(), $time->reveal(), $entity_type_manager, $this->container->get('entity_field.manager'), $price_calculator->reveal());
    $cron->invoke();
  }

  /**
   * Data provider for testDoInvoke.
   */
  public function dataDoInvoke() {
    $data['no-run:no-change'] = [
      'booking_windows' => [
        1 => '2018-05-31',
        2 => '2018-06-30',
        3 => '2018-10-30',
      ],
      'now' => strtotime('2018-04-01'),
      'last_run' => NULL,
      'event_ids' => NULL,
    ];

    $data['no-run:one-change'] = [
      'booking_windows' => [
        1 => '2018-05-31',
        2 => '2018-06-30',
        3 => '2018-10-30',
      ],
      'now' => strtotime('2018-06-01'),
      'last_run' => NULL,
      'event_ids' => [1 => "1"],
    ];

    $data['no-run:two-changes'] = [
      'booking_windows' => [
        1 => '2018-05-31',
        2 => '2018-06-30',
        3 => '2018-10-30',
      ],
      'now' => strtotime('2018-10-01'),
      'last_run' => NULL,
      'event_ids' => [1 => "1", 2 => "2"],
    ];

    $data['run:no-change'] = [
      'booking_windows' => [
        1 => '2018-05-31',
        2 => '2018-06-30',
        3 => '2018-10-30',
      ],
      'now' => strtotime('2018-06-10'),
      'last_run' => strtotime('2018-06-01'),
      'event_ids' => NULL,
    ];

    $data['run:one-change'] = [
      'booking_windows' => [
        1 => '2018-05-31',
        2 => '2018-06-30',
        3 => '2018-10-30',
      ],
      'now' => strtotime('2018-07-01'),
      'last_run' => strtotime('2018-06-01'),
      'event_ids' => [2 => "2"],
    ];

    return $data;
  }

}
