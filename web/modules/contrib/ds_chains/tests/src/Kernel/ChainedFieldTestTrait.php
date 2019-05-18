<?php

namespace Drupal\Tests\ds_chains\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines a test helper trait for ds_chains.
 */
trait ChainedFieldTestTrait {

  /**
   * Creates a test field.
   *
   * @param string $field_name
   *   Field name.
   * @param string $label
   *   Field label.
   * @param string $entity_type
   *   Entity type ID.
   * @param string $bundle
   *   Bundle.
   * @param string $field_type
   *   Field type.
   * @param array $settings
   *   Field settings.
   * @param array $instance_settings
   *   Instance settings.
   */
  protected function createTestField($field_name, $label, $entity_type = 'entity_test', $bundle = 'entity_test', $field_type = 'test_field', array $settings = [], array $instance_settings = []) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'settings' => $settings,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'settings' => $instance_settings,
      'label' => $label,
    ]);
    $field->save();
  }

  /**
   * Configure entity view display.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   Entity view display.
   */
  protected function configureEntityViewDisplay($field_name) {
    $plugin_id = 'ds_chains:entity_test/entity_test/user_id/' . $field_name;
    $display = $this->getEntityViewDisplay();
    $regions = $display->getThirdPartySetting('ds', 'regions', []);
    $regions['ds_content'][] = $plugin_id;
    $display->setThirdPartySetting('ds', 'regions', $regions);
    $fields = $display->getThirdPartySetting('ds', 'fields', []);
    $fields[$plugin_id] = [
      'plugin_id' => $plugin_id,
      'weight' => 1,
      'label' => 'hidden',
      'formatter' => 'field_test_default',
      'settings' => [
        'test_formatter_setting' => 'PONIES',
      ],
    ];
    $display->setThirdPartySetting('ds', 'fields', $fields);
    return $display;
  }

  /**
   * Gets the entity view display.
   *
   * @param string $view_mode
   *   View mode.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   Entity view display.
   */
  protected function getEntityViewDisplay($view_mode = 'default') {
    if (!$display = EntityViewDisplay::load('entity_test.entity_test.' . $view_mode)) {
      $display = EntityViewDisplay::create([
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
      $display->setThirdPartySetting('ds', 'layout', [
        'id' => 'ds_1col',
        'library' => NULL,
        'disable_css' => FALSE,
        'entity_classes' => 'all_classes',
        'settings' => [],
      ]);
    }
    return $display;
  }

}
