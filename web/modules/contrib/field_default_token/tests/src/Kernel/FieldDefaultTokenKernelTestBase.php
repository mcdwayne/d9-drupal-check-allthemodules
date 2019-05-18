<?php

namespace Drupal\Tests\field_default_token\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a common base class for kernel tests of Field Default Token module.
 */
abstract class FieldDefaultTokenKernelTestBase extends KernelTestBase  {

  /**
   * The ID of the entity type used in the test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle of the entity type used in the test.
   *
   * If this is left empty, the entity type ID will be used as a bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The name of the field used in the test.
   *
   * @var string
   */
  protected $fieldName = 'field_default_token_test';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'field_default_token'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema($this->entityTypeId);
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'string',
    ])->save();
  }

  /**
   * Creates a test field configuration.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   The field configuration.
   */
  protected function createField() {
    return FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle ?: $this->entityTypeId,
    ]);
  }

}
