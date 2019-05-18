<?php

namespace Drupal\Tests\datetime_range_timezone\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Helper methods for tests.
 */
trait DateRangeTimezoneHelperTrait {

  /**
   * Creates the field storage and config on the test entity.
   *
   * @param string $field_name
   *   (optional) The field name.
   */
  protected function setupDatetimeRangeTimezoneField($field_name = 'date') {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'daterange_timezone',
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field_config->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_timezone',
      ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_timezone',
      ])
      ->save();
  }

  /**
   * Creates the test entity.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date.
   * @param string $timezone
   *   The timezone as a string.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   *   The saved entity.
   */
  protected function createTestEntity(DrupalDateTime $start_date, DrupalDateTime $end_date, $timezone) {
    $entity = EntityTest::create([
      'date' => [
        'value' => $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'end_value' => $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'timezone' => $timezone,
      ],
    ]);
    $entity->save();
    return $entity;
  }

}
