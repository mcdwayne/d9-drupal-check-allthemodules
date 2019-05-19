<?php

/**
 * @file
 * Contains \Drupal\Tests\station_schedule\Unit\DatetimeHelperTest.
 */

namespace Drupal\Tests\station_schedule\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\station_schedule\DatetimeHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\station_schedule\DatetimeHelper
 * @group StationSchedule
 */
class DatetimeHelperTest extends UnitTestCase {

  /**
   * @covers ::deriveTimeFromMinutes
   * @dataProvider providerTestDeriveTimeFromMinutes
   */
  public function testDeriveTimeFromMinutes($minutes, $expected) {
    $result = DatetimeHelper::deriveTimeFromMinutes($minutes);
    $this->assertSame($expected, $result);
  }

  public function providerTestDeriveTimeFromMinutes() {
    $data = [];
    $data['am'] = [60, [
      'w' => 0,
      'G' => 1,
      'g' => 1,
      'H' => '01',
      'h' => '01',
      'i' => '00',
      'time' => '1',
      'minutes' => 60,
      'a' => 'am',
    ]];
    $data['pm'] = [780, [
      'w' => 0,
      'G' => 13,
      'g' => 1,
      'H' => '13',
      'h' => '01',
      'i' => '00',
      'time' => '1',
      'minutes' => 780,
      'a' => 'pm',
    ]];
    $data['minutes'] = [1, [
      'w' => 0,
      'G' => 0,
      'g' => 12,
      'H' => '00',
      'h' => '12',
      'i' => '01',
      'time' => '12:01',
      'minutes' => 1,
      'a' => 'am',
    ]];
    $data['more_than_a_day'] = [1500, [
      'w' => 1,
      'G' => 1,
      'g' => 1,
      'H' => '01',
      'h' => '01',
      'i' => '00',
      'time' => '1',
      'minutes' => 1500,
      'a' => 'am',
    ]];
    return $data;
  }

  /**
   * @covers ::hourRange
   * @dataProvider providerTestHourRange
   */
  public function testHourRange($start, $finish, $expected) {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $result = DatetimeHelper::hourRange($start, $finish);
    $this->assertSame($expected, (string) $result);
  }

  public function providerTestHourRange() {
    $data = [];
    $data[] = [0, 60, '12-1am'];
    $data[] = [0, 780, '12am-1pm'];
    return $data;
  }

}
