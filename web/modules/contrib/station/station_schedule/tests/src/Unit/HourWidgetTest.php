<?php

/**
 * @file
 * Contains \Drupal\Tests\station_schedule\Unit\HourWidgetTest.
 */

namespace Drupal\Tests\station_schedule\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\station_schedule\Plugin\Field\FieldWidget\Hour;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\station_schedule\Plugin\Field\FieldWidget\Hour
 * @group StationSchedule
 */
class HourWidgetTest extends UnitTestCase {

  /**
   * @covers ::hourOptions
   * @dataProvider providerTestHourOptions
   */
  public function testHourOptions($type, $expected) {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $widget = new Hour('hour', [], $field_definition->reveal(), [], []);
    $widget->setStringTranslation($this->getStringTranslationStub());

    $method = new \ReflectionMethod($widget, 'hourOptions');
    $method->setAccessible(TRUE);
    $options = $method->invoke($widget, $type);
    $this->assertSame($expected, $options);
  }

  public function providerTestHourOptions() {
    $data = [];
    $data['invalid'] = ['invalid', []];
    $data['start'] = ['start', [
      0 => '12:00am',
      1 => '1:00am',
      2 => '2:00am',
      3 => '3:00am',
      4 => '4:00am',
      5 => '5:00am',
      6 => '6:00am',
      7 => '7:00am',
      8 => '8:00am',
      9 => '9:00am',
      10 => '10:00am',
      11 => '11:00am',
      12 => '12:00pm',
      13 => '1:00pm',
      14 => '2:00pm',
      15 => '3:00pm',
      16 => '4:00pm',
      17 => '5:00pm',
      18 => '6:00pm',
      19 => '7:00pm',
      20 => '8:00pm',
      21 => '9:00pm',
      22 => '10:00pm',
      23 => '11:00pm',
    ]];
    $data['end'] = ['end', [
      1 => '1:00am',
      2 => '2:00am',
      3 => '3:00am',
      4 => '4:00am',
      5 => '5:00am',
      6 => '6:00am',
      7 => '7:00am',
      8 => '8:00am',
      9 => '9:00am',
      10 => '10:00am',
      11 => '11:00am',
      12 => '12:00pm',
      13 => '1:00pm',
      14 => '2:00pm',
      15 => '3:00pm',
      16 => '4:00pm',
      17 => '5:00pm',
      18 => '6:00pm',
      19 => '7:00pm',
      20 => '8:00pm',
      21 => '9:00pm',
      22 => '10:00pm',
      23 => '11:00pm',
      24 => '12:00am',
      25 => '1:00am the next day',
      26 => '2:00am the next day',
      27 => '3:00am the next day',
      28 => '4:00am the next day',
      29 => '5:00am the next day',
      30 => '6:00am the next day',
      31 => '7:00am the next day',
      32 => '8:00am the next day',
      33 => '9:00am the next day',
      34 => '10:00am the next day',
      35 => '11:00am the next day',
    ]];
    return $data;
  }

}
