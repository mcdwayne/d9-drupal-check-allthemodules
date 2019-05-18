<?php

namespace Drupal\Tests\tour_importer\Unit;


use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Drupal\cronpub\CronpubIcalService;

class CronpubIcalServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private $prophet;

  /**
   * {@inherited}
   */
  protected function setUp() {
    $this->prophet = new Prophet();

  }

  /**
   * Provides data for the testCentimetersToInches method.
   *
   * @return array
   */
  public function startEndRruleValue() {
    $timezone = new \DateTimeZone('Europe/Berlin');
    return [
      [
        [
          'start' => new DrupalDateTime(time() ,$timezone),
          'end' => new DrupalDateTime(time() + 360, $timezone),
          'rrule' => 'INTERVAL=2;FREQ=WEEKLY;BYDAY=FR;COUNT=6'
        ]
      ],
    ];
  }

  /**
   * {@inherited}
   */
  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  /**
   * Tests centimetersToInches method.
   *
   * @dataProvider startEndRruleValue
   */
  public function testServiceConstructed($data) {
    $service = new CronpubIcalService($data);


    $this->assertTrue(isset($service));
  }
}