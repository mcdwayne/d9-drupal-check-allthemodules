<?php

namespace Drupal\Tests\handy_cache_tags\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\handy_cache_tags\HandyCacheTagsManager;
use Drupal\Tests\UnitTestCase;

/**
 * Base class with some useful things.
 */
abstract class HandyCacheTagsBase extends UnitTestCase {

  /**
   * Gets a mock entity.
   */
  protected function getMockEntity($supply_expects = FALSE) {
    $mock_entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    if ($supply_expects) {
      return $mock_entity;
    }
    $mock_entity->expects($this->exactly(2))
      ->method('getEntityTypeId')
      ->willReturn('mock_entity');
    $mock_entity->expects($this->once())
      ->method('bundle')
      ->willReturn('mock_bundle');
    return $mock_entity;
  }

  /**
   * Gets a new container, that we can populate and set.
   */
  protected function getNewContainer() {
    $container = new ContainerBuilder();
    // The handler has no dependencies, so we can use it directly.
    $tag_manager = new HandyCacheTagsManager();
    $container->set('handy_cache_tags.manager', $tag_manager);
    return $container;
  }

}
