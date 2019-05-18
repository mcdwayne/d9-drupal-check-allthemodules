<?php

namespace Drupal\Tests\entity_type\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the entity type config entity functionality.
 *
 * @group entity_type
 */
class EntityTypeConfigImportTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_type',
    'entity_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_type_config');
    $this->installConfig(['entity_type_test']);
  }

  /**
   * Tests the entity type config entity import.
   */
  public function testEntityImport() {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $storage = $entity_type_manager->getStorage('entity_type_config');

    $entity = $storage->load('test');

    $this->assertTrue(isset($entity));
  }

}
