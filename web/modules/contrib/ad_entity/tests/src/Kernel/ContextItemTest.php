<?php

namespace Drupal\Tests\ad_entity\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests for context field items.
 *
 * @group ad_entity
 */
class ContextItemTest extends FieldKernelTestBase {

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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ad_entity',
    'ad_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'ad_context',
      'type' => 'ad_entity_context',
      'entity_type' => 'entity_test',
      'settings' => [],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'default_value' => [],
      ],
    ]);
    $this->field->save();
  }

  /**
   * Test for context field items on new entities.
   */
  public function testNew() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $new_test_entity */
    $new_test_entity = $entity_type_manager->getStorage('entity_test')->create([]);
    // The field must be there, and empty.
    $this->assertTrue($new_test_entity->hasField('ad_context'));
    $this->assertTrue($new_test_entity->get('ad_context')->isEmpty());

    $new_context_values = [
      'context_plugin_id' => 'turnoff',
      'context_settings' => [],
      'apply_on' => [],
    ];

    $new_test_entity->get('ad_context')->setValue($new_context_values);
    $this->assertTrue(!$new_test_entity->get('ad_context')->isEmpty());
  }

}
