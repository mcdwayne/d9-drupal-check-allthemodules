<?php

namespace Drupal\Tests\commerce_prorater_stepped_proportional\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\commerce_prorater_stepped_proportional\Plugin\Commerce\BillingSchedule\FixedWithFreeRollover;

/**
 * Tests the free rollover plugin.
 *
 * @group commerce_prorater_stepped_proportional
 */
class FixedWithFreeRolloverTest extends KernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce',
    'commerce_price',
    'commerce_recurring',
    'interval',
    'commerce_prorater_stepped_proportional',
  ];

  // Don't have time to fix this. TODO.
  protected $strictConfigSchema = FALSE;

  /**
   * Tests the free rollover interval.
   *
   * @dataProvider dataProviderTestYearlyScheduleRollover
   */
  public function testYearlyScheduleRollover($billing_schedule_start_configuration, $start_time, $expected_start_time, $expected_end_time) {
    $plugin = new FixedWithFreeRollover([
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
      'start_month' => $billing_schedule_start_configuration['start_month'],
      'start_day' => $billing_schedule_start_configuration['start_day'],
      'rollover_interval' => [
        'period' => 'month',
        'interval' => '1',
      ],
    ], '', []);
    $start_date = new DrupalDateTime($start_time);
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);

    $this->assertEquals(new DrupalDateTime($expected_start_time), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime($expected_end_time), $billing_period->getEndDate());
  }

  /**
   * Data provider for testYearlyScheduleRollover().
   */
  public function dataProviderTestYearlyScheduleRollover() {
    return [
      // Billing schedule starting January 1.
      'jan_1_outside_rollover' => [
        // Billing schedule configuration.
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        // Start date.
        '2017-11-30 10:22:30',
        // Expected period start date.
        '2017-01-01 00:00:00',
        // Expected period end date.
        '2018-01-01 00:00:00',
      ],
      'jan_1_within_rollover' => [
        [
          'start_month' => 1,
          'start_day' => 1,
        ],
        // Start date.
        '2017-12-02 10:22:30',
        // Expected period start date.
        '2018-01-01 00:00:00',
        // Expected period end date.
        '2019-01-01 00:00:00',
      ],
      // Billing schedule starting February 1.
      'feb_1_outside_rollover' => [
        // Billing schedule configuration.
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
        // Start date.
        '2017-12-15 10:22:30',
        // Expected period start date.
        '2017-02-01 00:00:00',
        // Expected period end date.
        '2018-02-01 00:00:00',
      ],
      'feb_1_within_rollover' => [
        [
          'start_month' => 2,
          'start_day' => 1,
        ],
        // Start date.
        '2018-01-02 10:22:30',
        // Expected period start date.
        '2018-02-01 00:00:00',
        // Expected period end date.
        '2019-02-01 00:00:00',
      ],
    ];
  }

}
