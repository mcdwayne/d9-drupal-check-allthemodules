<?php

namespace Drupal\Tests\blizz_bulk_creator\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Trait UnitTestHelperTrait.
 *
 * Contains methods to help creating mock objects.
 *
 * @package Drupal\tests\blizz_bulk_creator\Unit
 */
trait UnitTestMocksTrait {

  /**
   * Helper method for the entity type manager.
   *
   * @param \Prophecy\Prophecy\ObjectProphecy $entity_type_manager
   *   The object prophecy.
   * @param string $entity_type
   *   The name of the entity type.
   * @param array $entities
   *   The array that should be returned by the mocked entity storage
   *   loadMultiple() method.
   */
  private function entityStorageLoadMultiple(
    ObjectProphecy $entity_type_manager,
    string $entity_type,
    array $entities = []
  ) {
    // We need an entity storage mock object.
    $storage_interface = $this->prophesize(EntityStorageInterface::class);

    // Return the expected result.
    $storage_interface->loadMultiple()->willReturn($entities);

    $entity_type_manager
      ->getStorage($entity_type)
      ->willReturn($storage_interface->reveal());
  }

}
