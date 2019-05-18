<?php

namespace Drupal\Tests\commerce_prorater_stepped_proportional\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the prorater plugin.
 *
 * @group commerce_prorater_stepped_proportional
 */
class SteppedProportionalTest extends RecurringKernelTestBase {

  /**
   * The prorater manager.
   *
   * @var \Drupal\commerce_recurring\ProraterManager
   */
  protected $proraterManager;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'commerce_order',
    'commerce_price',
    'commerce_recurring',
    'interval',
    'commerce_prorater_stepped_proportional',
  ];

  // Don't have time to fix this. TODO.
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->proraterManager = $this->container->get('plugin.manager.commerce_prorater');
  }

  /**
   * covers ::prorateOrderItem
   * @dataProvider dataProviderTestProrating
   */
  public function testYearlyScheduleProrating($billing_schedule_start_configuration, $current_time, $expected_price) {
    // Set current time.
    $current_timestamp = strtotime($current_time);
    $this->rewindTime($current_timestamp);
    $current_date = DrupalDateTime::createFromTimestamp($current_timestamp);

    // Need to change the billing schedule that the parent class sets up to be
    // fixed yearly.
    $this->billingSchedule->setPluginId('fixed');
    $this->billingSchedule->setPluginConfiguration([
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
      'start_month' => $billing_schedule_start_configuration['start_month'],
      'start_day' => $billing_schedule_start_configuration['start_day'],
    ]);
    // Set our prorater.
    $this->billingSchedule->setProraterId('stepped_proportional');
    $this->billingSchedule->setProraterConfiguration([
      'steps' => 4,
      'step_interval' => [
        'period' => 'month',
        'interval' => '3',
      ],
    ]);
    $this->billingSchedule->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $this->variation->getOrderItemTitle(),
      'purchased_entity' => $this->variation->id(),
      'unit_price' => new Price('30', 'USD'),
    ]);
    $order_item->save();

    $full_period = $this->billingSchedule->getPlugin()->generateFirstBillingPeriod($current_date);

    $partial_period = new BillingPeriod(
      $current_date,
      $full_period->getEndDate()
    );

    //dump($this->billingSchedule->getProraterId());
    $prorated_unit_price = $this->billingSchedule->getProrater()->prorateOrderItem($order_item, $partial_period, $full_period);

    $this->assertEquals($expected_price, $prorated_unit_price);
  }

  /**
   * Data provider for testProrating().
   */
  public function dataProviderTestProrating() {
    return [
      // Billing schedule starting January 1.
      'jan_1_full' => [
        // Billing schedule configuration.
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        // Current time.
        '2017-01-01 00:00',
        // Expected pro-rated price
        new Price('30', 'USD'),
      ],
      'jan_1_Q1' => [
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        '2017-02-24 19:00',
        new Price('30', 'USD'),
      ],
      'jan_1_Q2' => [
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        '2017-04-24 12:00',
        new Price('22.50', 'USD'),
      ],
      'jan_1_Q3' => [
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
      '2017-07-12 03:00',
        new Price('15', 'USD'),
      ],
      'jan_1_Q4' => [
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        '2017-11-03 21:00',
        new Price('7.50', 'USD'),
      ],
      // Billing schedule starting February 1.
      'feb_1_full' => [
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
        '2017-02-01 00:00',
        new Price('30', 'USD'),
      ],
      'feb_1_Q1' => [
        // Q1 runs feb - mar - apr.
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
        '2017-04-03 19:00',
        new Price('30', 'USD'),
      ],
      'feb_1_Q2' => [
        // Q2 runs may - jun - jul.
      [
          'start_month' => 2,
          'start_day' => 1,
        ],
        '2017-06-24 12:00',
        new Price('22.50', 'USD'),
      ],
      'feb_1_Q3' => [
        // Q3 runs aug - sep - oct.
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
      '2017-10-12 03:00',
        new Price('15', 'USD'),
      ],
      'feb_1_Q4' => [
        // Q4 runs nov - dec - jan.
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
        '2018-01-13 21:00',
        new Price('7.50', 'USD'),
      ],
    ];
  }

}
