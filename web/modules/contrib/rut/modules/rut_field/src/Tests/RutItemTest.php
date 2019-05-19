<?php

namespace Drupal\rut_field\Tests;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rut\Rut;

/**
 * Tests the new entity API for the rut_field field type.
 *
 * @group rut_field
 */
class RutItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rut_field'];

  protected function setUp() {
    parent::setUp();

    // Create a rut_field field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'rut_field_rut',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the rut_field field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test');
    list($rut, $dv) = Rut::generateRut(FALSE, 19, 2000);
    $entity->field_test->rut = $rut;
    $entity->field_test->dv = $dv;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test->rut, $rut);
    $this->assertEqual($entity->field_test[0]->rut, $rut);
    $this->assertEqual($entity->field_test->dv, $dv);
    $this->assertEqual($entity->field_test[0]->dv, $dv);

    // Verify changing the field value.
    $new_value = Rut::generateRut(TRUE, 100000, 10000000);
    $entity->field_test->value = $new_value;
    $entity->save();

    // Read changed entity and assert changed values.
    list($new_rut, $new_dv) = Rut::separateRut($new_value);

    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->rut, $new_rut);
    $this->assertEqual($entity->field_test->dv, $new_dv);
    $this->assertEqual($entity->field_test->value, $new_value);

    // Test sample item generation.
    $entity = entity_create('entity_test');
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);

    // Test invalid rut.
    $entity = entity_create('entity_test');
    $invalid_rut = 'invalid';
    $entity->field_test->value = $invalid_rut;
    $entity->name->value = $this->randomMachineName();
    $violations = $entity->validate();
    $this->assertTrue($violations->count() > 0, $invalid_rut . ' is a rut invalid.');
  }

}
