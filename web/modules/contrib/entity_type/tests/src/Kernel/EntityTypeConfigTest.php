<?php

namespace Drupal\Tests\entity_type\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the entity type config entity functionality.
 *
 * @group entity_type
 */
class EntityTypeConfigTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_type',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_type_config');
  }

  /**
   * Tests the entity type config entity creation.
   */
  public function testEntityCreation() {
    $storage = $this->entityManager->getStorage('entity_type_config');
    $entity_id = $this->randomMachineName(8);
    $entity = $storage->create([
      'id' => $entity_id,
    ]);

    $entity->save();

    $entity = $storage->load($entity_id);

    $this->assertTrue(isset($entity));
  }

}
