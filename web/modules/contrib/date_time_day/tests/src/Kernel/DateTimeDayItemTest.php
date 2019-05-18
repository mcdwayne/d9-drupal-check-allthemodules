<?php

namespace Drupal\Tests\date_time_day\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Test date_time_day field type via API.
 *
 * @group date_time_day
 */
class DateTimeDayItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'date_time_day',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add a date_time_day field.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'datetimeday',
      'settings' => ['datetime_type' => DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    $display_options = [
      'type' => 'datetimeday_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'fallback',
        'time_format_type' => 'fallback',
        'day_separator' => 'UNTRANSLATED',
        'time_separator' => 'UNTRANSLATED',
      ],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldStorage->getName(), $display_options)
      ->save();
  }

  /**
   * Tests the field configured for time-only.
   */
  public function testDateDayTimeOnly() {
    $this->fieldStorage->setSetting('datetime_type', DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT);
    $field_name = $this->fieldStorage->getName();
    // Create an entity.
    $entity = EntityTest::create([
      'name' => $this->randomString(),
      $field_name => [
        'value' => '2018-02-06',
        'start_time_value' => '10:00',
        'end_time_value' => '10:00',
      ],
    ]);

    // Dates are saved without a time value. When they are converted back into
    // a \Drupal\datetime\DateTimeComputed object they should all have the same
    // time.
    $start_time = $entity->{$field_name}->start_time;
    sleep(1);
    $end_time = $entity->{$field_name}->end_time;
    $this->assertEquals($start_time->getTimestamp(), $end_time->getTimestamp());
  }

}
