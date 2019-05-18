<?php

namespace Drupal\Tests\datetime_range_timezone\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the default formatter.
 *
 * @group datetime_range_timezone
 */
class DateRangeTimezoneFormatterTest extends KernelTestBase {

  use DateRangeTimezoneHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'entity_test',
    'datetime',
    'datetime_range',
    'datetime_range_timezone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('entity_test');

    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->save();

    $this->setupDatetimeRangeTimezoneField();
  }

  /**
   * Ensure the field is rendered in the correct timezone.
   */
  public function testRenderedInCorrectTimezone() {
    $start_date = new DrupalDateTime('2017-03-25 10:30:00', 'UTC');
    $end_date = new DrupalDateTime('2017-03-28 10:30:00', 'UTC');
    $entity = $this->createTestEntity($start_date, $end_date, 'America/New_York');

    // Convert our dates to America/New_York.
    $timezone = new \DateTimeZone('America/New_York');
    $start_date->setTimeZone($timezone);
    $end_date->setTimeZone($timezone);

    // Render the field.
    $output = $entity->date->view([]);

    // Ensure the daterange is formatted using America/New York timezone using
    // the medium date format type.
    $this->assertEquals($start_date->format('D, m/d/Y - H:i'), $output[0]['#start_date']);
    $this->assertEquals($end_date->format('D, m/d/Y - H:i'), $output[0]['#end_date']);

    // Ensure the nice version of the timezone is printed.
    $this->assertEquals('America/New York', (string) $output[0]['#timezone']);
  }

  /**
   * If we disable the timezone it should not be displayed.
   */
  public function testRenderedWithoutTimezone() {
    $start_date = new DrupalDateTime('2017-03-25 10:30:00', 'UTC');
    $end_date = new DrupalDateTime('2017-03-28 10:30:00', 'UTC');
    $entity = $this->createTestEntity($start_date, $end_date, 'America/New_York');

    // Timezone is displayed by default.
    $output = $entity->date->view([]);
    $this->assertEquals('America/New York', (string) $output[0]['#timezone']);

    $output = $entity->date->view([
      'settings' => [
        'format_type' => 'medium',
        'display_timezone' => FALSE,
      ],
    ]);
    $this->assertNull($output[0]['#timezone']);
  }

}
