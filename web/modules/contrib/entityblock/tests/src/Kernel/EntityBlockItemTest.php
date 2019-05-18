<?php

namespace Drupal\Tests\entityblock\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the new entity API for the entityblock field type.
 *
 * @group entityblock
 */
class EntityBlockItemTest extends FieldKernelTestBase  {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entityblock');

  protected function setUp() {
    parent::setUp();

    // Create an entityblock field for validation.
    FieldStorageConfig::create(array(
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'entityblock',
    ))->save();
    FieldConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Tests using entity fields of the link field type.
   */
  public function testEntityBlockItem() {
    // Create entity.
    $entity = EntityTest::create();
    $title = $this->randomMachineName();
    $view_mode = 'full';

    $entity->field_test->enabled = 1;
    $entity->field_test->title = $title;
    $entity->field_test->view_mode = $view_mode;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($entity->field_test->title, $title);
    $this->assertEquals($entity->field_test[0]->view_mode, $view_mode);

    // Update only the entity name property to check if the link field data will
    // remain intact.
    $entity->name->value = $this->randomMachineName();
    $entity->save();
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEquals($entity->field_test->title, $title);
    $this->assertEquals($entity->field_test[0]->view_mode, $view_mode);

    // Verify changing the field value.
    $new_title = $this->randomMachineName();
    $entity->field_test->title = $new_title;
    $this->assertEquals($entity->field_test->title, $new_title);
    $this->assertEquals($entity->field_test[0]->view_mode, $view_mode);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($entity->field_test->title, $new_title);
    $this->assertEquals($entity->field_test[0]->view_mode, $view_mode);

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
