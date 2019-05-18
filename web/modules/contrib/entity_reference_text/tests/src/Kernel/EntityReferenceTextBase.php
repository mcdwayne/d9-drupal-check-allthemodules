<?php

namespace Drupal\Tests\entity_reference_text\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

abstract class EntityReferenceTextBase extends KernelTestBase {

  /**
   * The field storage.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorage;

  /**
   * The field.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_reference_text', 'entity_test', 'field', 'user', 'options', 'system'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'entity_references_text',
      'settings' => [
        'target_type' => 'entity_test',
        'target_bundle' => 'entity_test',
      ],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'handler' => 'default',
      ],
    ]);
    $this->field->save();
  }

}
