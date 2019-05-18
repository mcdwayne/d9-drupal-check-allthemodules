<?php

namespace Drupal\Tests\private_entity\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Sets up private_entity field test and helpers definition.
 *
 * @group private_entity
 */
abstract class PrivateEntityTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'field_ui',
    'entity_test',
    'node',
    'node_access_test',
    'private_entity',
  ];

  /**
   * A user with permission to administer content and test entities.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An array of display options to pass to EntityViewDisplay.
   *
   * @var array
   */
  protected $displayOptions;

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The private_entity field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The private_entity field name used in this test class.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Attaches a field to an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_bundle
   *   The entity bundle.
   */
  protected function attachField($entity_type_id, $entity_bundle) {
    $this->fieldName = 'field_private';
    $type = 'private_entity';
    $widget_type = 'private_entity_default_widget';
    $formatter_type = 'private_entity_default_formatter';

    // Add the private_entity field to the entity type.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $entity_type_id,
      'type' => $type,
      // 'cardinality' => -1,
      // 'translatable' => FALSE,.
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'label' => 'Private',
    // Should not be necessary.
      'entity_type' => $entity_type_id,
      'bundle' => $entity_bundle,
    ]);
    $this->field->save();

    entity_get_form_display($entity_type_id, $entity_bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => $widget_type,
      ])
      ->save();

    entity_get_display($entity_type_id, $entity_bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => $formatter_type,
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Returns the entity form for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_bundle
   *   The entity bundle.
   * @param string $operation
   *   Operation: add, edit or delete.
   *
   * @return string
   *   The path to the entity form.
   */
  protected function getEntityTypeFormPath($entity_type_id, $entity_bundle, $operation = 'add') {
    $result = '';
    switch ($entity_type_id) {
      case 'entity_test':
        $result = $entity_type_id . '/' . $operation;
        break;
    }
    return $result;
  }

}
