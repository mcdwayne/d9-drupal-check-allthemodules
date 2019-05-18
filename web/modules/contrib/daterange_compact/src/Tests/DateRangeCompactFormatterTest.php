<?php

namespace Drupal\daterange_compact\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\daterange_compact\Entity\DateRangeFormat;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests compact date range field formatter functionality.
 *
 * @group field
 */
class DateRangeCompactFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'field',
    'datetime',
    'datetime_range',
    'daterange_compact',
    'entity_test',
    'user',
  ];

  /**
   * The name of the entity type used in testing.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The name of the bundle used for testing.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The made up name of the date-only range field.
   *
   * @var string
   */
  protected $dateFieldName;

  /**
   * The made up name of the date & time range field.
   *
   * @var string
   */
  protected $dateTimeFieldName;

  /**
   * The default display for this entity.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $defaultDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['daterange_compact']);
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->dateFieldName = Unicode::strtolower($this->randomMachineName());
    $this->dateTimeFieldName = Unicode::strtolower($this->randomMachineName());

    // Create a typical format for USA.
    $usa_format = DateRangeFormat::create([
      'id' => 'usa',
      'label' => 'USA',
      'date_settings' => [
        'default_pattern' => 'F jS, Y',
        'separator' => ' - ',
        'same_month_start_pattern' => 'F jS',
        'same_month_end_pattern' => 'jS, Y',
        'same_year_start_pattern' => 'F jS',
        'same_year_end_pattern' => 'F jS, Y',
      ],
      'datetime_settings' => [
        'default_pattern' => 'g:ia \o\n F jS, Y',
        'separator' => ' - ',
        'same_day_start_pattern' => 'g:ia',
        'same_day_end_pattern' => 'g:ia \o\n F jS, Y',
      ],
    ]);
    $usa_format->save();

    // Create a ISO-8601 format without any compact variations.
    $iso_8601_format = DateRangeFormat::create([
      'id' => 'iso_8601',
      'label' => 'ISO-8601',
      'date_settings' => [
        'default_pattern' => 'Y-m-d',
        'separator' => ' - ',
      ],
      'datetime_settings' => [
        'default_pattern' => 'Y-m-d\TH:i:s',
        'separator' => ' - ',
      ],
    ]);
    $iso_8601_format->save();

    $date_field_storage = FieldStorageConfig::create([
      'field_name' => $this->dateFieldName,
      'entity_type' => $this->entityType,
      'type' => 'daterange',
      'settings' => [
        'datetime_type' => DateTimeItem::DATETIME_TYPE_DATE,
      ],
    ]);
    $date_field_storage->save();

    $date_field_instance = FieldConfig::create([
      'field_storage' => $date_field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $date_field_instance->save();

    $date_time_field_storage = FieldStorageConfig::create([
      'field_name' => $this->dateTimeFieldName,
      'entity_type' => $this->entityType,
      'type' => 'daterange',
      'settings' => [
        'datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $date_time_field_storage->save();

    $date_time_field_instance = FieldConfig::create([
      'field_storage' => $date_time_field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $date_time_field_instance->save();

    $this->defaultDisplay = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $this->defaultDisplay->setComponent($this->dateFieldName, [
      'type' => 'daterange_compact',
      'settings' => [
        'format_type' => 'medium',
      ],
    ]);
    $this->defaultDisplay->setComponent($this->dateTimeFieldName, [
      'type' => 'daterange_compact',
      'settings' => [
        'format_type' => 'medium',
      ],
    ]);
    $this->defaultDisplay->save();
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

  /**
   * Tests the display of date-only range fields.
   */
  public function testDateRanges() {
    $all_data = [];

    // Same day.
    $all_data[] = [
      'start' => '2017-01-01',
      'end' => '2017-01-01',
      'expected' => [
        'default' => '1 January 2017',
        'usa' => 'January 1st, 2017',
        'iso_8601' => '2017-01-01',
      ],
    ];

    // Different days, same month.
    $all_data[] = [
      'start' => '2017-01-02',
      'end' => '2017-01-03',
      'expected' => [
        'default' => '2–3 January 2017',
        'usa' => 'January 2nd - 3rd, 2017',
        'iso_8601' => '2017-01-02 - 2017-01-03',
      ],
    ];

    // Different months, same year.
    $all_data[] = [
      'start' => '2017-01-04',
      'end' => '2017-02-05',
      'expected' => [
        'default' => '4 January–5 February 2017',
        'usa' => 'January 4th - February 5th, 2017',
        'iso_8601' => '2017-01-04 - 2017-02-05',
      ],
    ];

    // Different years.
    $all_data[] = [
      'start' => '2017-01-06',
      'end' => '2018-02-07',
      'expected' => [
        'default' => '6 January 2017–7 February 2018',
        'usa' => 'January 6th, 2017 - February 7th, 2018',
        'iso_8601' => '2017-01-06 - 2018-02-07',
      ],
    ];

    foreach ($all_data as $data) {
      foreach ($data['expected'] as $format => $expected) {
        $entity = EntityTest::create([]);
        $entity->{$this->dateFieldName}->value = $data['start'];
        $entity->{$this->dateFieldName}->end_value = $data['end'];

        if ($format === 'default') {
          $this->renderEntityFields($entity, $this->defaultDisplay);
        }
        else {
          // For testing a custom format, don't use the entity's default
          // display, but instead render the field using a temporary one.
          $display = $this->buildCustomDisplay($this->dateFieldName, $format);
          $this->renderEntityFields($entity, $display);
        }

        $this->assertRaw($expected);
      }
    }
  }

  /**
   * Tests the display of date and time range fields.
   */
  public function testDateTimeRanges() {
    $all_data = [];

    // Note: the default timezone for unit tests is Australia/Sydney
    // see https://www.drupal.org/node/2498619 for why
    // Australia/Sydney is UTC +10:00 (normal) or UTC +11:00 (DST)
    // DST starts first Sunday in October
    // DST ends first Sunday in April.
    // Same day.
    $all_data[] = [
      'start' => '2017-01-01T09:00:00',
      'end' => '2017-01-01T12:00:00',
      'expected' => [
        'default' => '1 January 2017 20:00–23:00',
        'usa' => '8:00pm - 11:00pm on January 1st, 2017',
        'iso_8601' => '2017-01-01T20:00:00 - 2017-01-01T23:00:00',
      ],
    ];

    // Different day in UTC, same day in Australia.
    $all_data[] = [
      'start' => '2017-01-01T23:00:00',
      'end' => '2017-01-02T01:00:00',
      'expected' => [
        'default' => '2 January 2017 10:00–12:00',
        'usa' => '10:00am - 12:00pm on January 2nd, 2017',
        'iso_8601' => '2017-01-02T10:00:00 - 2017-01-02T12:00:00',
      ],
    ];

    // Same day in UTC, different day in Australia.
    $all_data[] = [
      'start' => '2017-01-01T12:00:00',
      'end' => '2017-01-01T15:00:00',
      'expected' => [
        'default' => '1 January 2017 23:00–2 January 2017 02:00',
        'usa' => '11:00pm on January 1st, 2017 - 2:00am on January 2nd, 2017',
        'iso_8601' => '2017-01-01T23:00:00 - 2017-01-02T02:00:00',
      ],
    ];

    // Different days in UTC and Australia, also spans DST change.
    $all_data[] = [
      'start' => '2017-04-01T01:00:00',
      'end' => '2017-04-08T01:00:00',
      'expected' => [
        'default' => '1 April 2017 12:00–8 April 2017 11:00',
        'usa' => '12:00pm on April 1st, 2017 - 11:00am on April 8th, 2017',
        'iso_8601' => '2017-04-01T12:00:00 - 2017-04-08T11:00:00',
      ],
    ];

    foreach ($all_data as $data) {
      foreach ($data['expected'] as $format => $expected) {
        $entity = EntityTest::create([]);
        $entity->{$this->dateTimeFieldName}->value = $data['start'];
        $entity->{$this->dateTimeFieldName}->end_value = $data['end'];

        if ($format === 'default') {
          $this->renderEntityFields($entity, $this->defaultDisplay);
        }
        else {
          // For testing a custom format, don't use the entity's default
          // display, but instead render the field using a temporary one.
          $display = $this->buildCustomDisplay($this->dateTimeFieldName, $format);
          $this->renderEntityFields($entity, $display);
        }

        $this->assertRaw($expected);
      }
    }
  }

  /**
   * Helper function that creates a display object for the given format.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $format
   *   The name of the format.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   A temporary display object.
   */
  private function buildCustomDisplay($field_name, $format) {
    $display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->setComponent($field_name, [
      'type' => 'daterange_compact',
      'settings' => [
        'format_type' => $format,
      ],
    ]);
    return $display;
  }

}
