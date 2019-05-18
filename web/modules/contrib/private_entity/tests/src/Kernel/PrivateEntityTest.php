<?php

namespace Drupal\Tests\private_entity\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the entity API for the private_entity field type.
 *
 * @group private_entity
 */
class PrivateEntityTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['private_entity'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a private field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_private',
      'entity_type' => 'entity_test',
      'type' => 'private_entity',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_private',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the private_entity field type.
   */
  public function testPrivateEntityItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = 0;
    $entity->field_private = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_private instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertEqual($entity->field_private->value, $value);

    // Verify changing the field value.
    $new_value = 1;
    $entity->field_private->value = $new_value;
    $this->assertEqual($entity->field_private->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_private->value, $new_value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_private->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
