<?php

namespace Drupal\Tests\healthz\Unit;

use Drupal\healthz\Plugin\HealthzCheck\LastCronRun;

/**
 * @coversDefaultClass \Drupal\healthz\Plugin\HealthzCheck\LastCronRun
 * @group healthz
 */
class LastCronRunTest extends HealthzUnitTestBase {

  /**
   * @covers ::check
   * @dataProvider checkTestCases
   */
  public function testCheck($last_cron_run, $request_time, $expected) {
    $health_check = new LastCronRun(['failure_threshold' => 172800], '', ['provider' => 'test'], $last_cron_run, $request_time);
    $this->assertEquals($expected, $health_check->check());
  }

  /**
   * Data provider for ::testCheck.
   */
  public function checkTestCases() {
    return [
      '500 seconds elapsed' => [
        1000,
        1500,
        TRUE,
      ],
      'No seconds elapsed' => [
        1000,
        1000,
        TRUE,
      ],
      'Cron ran in the future' => [
        1000,
        1001,
        TRUE,
      ],
      'Threshold reached' => [
        1000,
        200000,
        FALSE,
      ],
    ];
  }

}
