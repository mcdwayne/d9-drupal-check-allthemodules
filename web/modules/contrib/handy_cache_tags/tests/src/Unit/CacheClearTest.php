<?php

namespace Drupal\Tests\handy_cache_tags\Unit;

use Drupal\handy_cache_tags\HandyCacheTagsHandler;
use Drupal\Tests\PhpunitCompatibilityTrait;

/**
 * Test tag name generation.
 *
 * @group handy_cache_tags
 */
class CacheClearTest extends HandyCacheTagsBase {

  use PhpunitCompatibilityTrait;

  /**
   * Tests procedural with no mocking.
   */
  public function testProceduralRegularEntity() {
    $container = $this->getNewContainer();
    $mock_cache_clearer = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');
    $mock_cache_clearer->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'handy_cache_tags:mock_entity',
        'handy_cache_tags:mock_entity:mock_bundle',
      ]);
    $container->set('cache_tags.invalidator', $mock_cache_clearer);
    \Drupal::setContainer($container);
    require_once __DIR__ . '/../../../handy_cache_tags.module';
    _handy_cache_tags_clear_entity_tags($this->getMockEntity());
  }

  /**
   * Test procedural with a config entity bundle.
   */
  public function testProceduralConfigEntityBundle() {
    $container = $this->getNewContainer();
    $mock_cache_clearer = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');
    $mock_cache_clearer->expects($this->exactly(2))
      ->method('invalidateTags')
      ->withConsecutive(
        [['handy_cache_tags:mock_entity', 'handy_cache_tags:mock_entity:mock_bundle']],
        [['handy_cache_tags:mock_node_bundle:id', 'handy_cache_tags:mock_node_bundle']]
      );
    $container->set('cache_tags.invalidator', $mock_cache_clearer);
    \Drupal::setContainer($container);
    $mock_entity = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityBundleBase');
    $mock_entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $mock_entity_type->expects($this->once())
      ->method('getBundleOf')
      ->willReturn('mock_node_bundle');
    $mock_entity->expects($this->exactly(2))
      ->method('getEntityTypeId')
      ->willReturn('mock_entity');
    $mock_entity->expects($this->once())
      ->method('id')
      ->willReturn('id');
    $mock_entity->expects($this->once())
      ->method('bundle')
      ->willReturn('mock_bundle');
    $mock_entity->expects($this->once())
      ->method('getEntityType')
      ->willReturn($mock_entity_type);
    require_once __DIR__ . '/../../../handy_cache_tags.module';
    _handy_cache_tags_clear_entity_tags($mock_entity);
  }

  /**
   * Test procedural with a config entity bundle.
   */
  public function testProceduralFieldStorageConfig() {
    $container = $this->getNewContainer();
    $mock_cache_clearer = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');
    $mock_cache_clearer->expects($this->exactly(2))
      ->method('invalidateTags')
      ->withConsecutive(
        [['handy_cache_tags:mock_entity', 'handy_cache_tags:mock_entity:mock_bundle']],
        [['handy_cache_tags:target_bundle']]
      );
    $container->set('cache_tags.invalidator', $mock_cache_clearer);
    \Drupal::setContainer($container);
    $mock_entity = $this->createMock('Drupal\field\Entity\FieldStorageConfig');
    $mock_entity->expects($this->exactly(2))
      ->method('getEntityTypeId')
      ->willReturn('mock_entity');
    $mock_entity->expects($this->once())
      ->method('bundle')
      ->willReturn('mock_bundle');
    $mock_entity->expects($this->once())
      ->method('getTargetEntityTypeId')
      ->willReturn('target_bundle');
    require_once __DIR__ . '/../../../handy_cache_tags.module';
    _handy_cache_tags_clear_entity_tags($mock_entity);
  }

  /**
   * Test procedural with field config.
   */
  public function testProceduralFieldConfig() {
    $container = $this->getNewContainer();
    $mock_cache_clearer = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');
    $mock_cache_clearer->expects($this->exactly(2))
      ->method('invalidateTags')
      ->withConsecutive(
        [['handy_cache_tags:mock_entity', 'handy_cache_tags:mock_entity:mock_bundle']],
        [['handy_cache_tags:target_bundle:target_type', 'handy_cache_tags:target_type']]
      );
    $container->set('cache_tags.invalidator', $mock_cache_clearer);
    \Drupal::setContainer($container);
    $mock_entity = $this->createMock('Drupal\field\Entity\FieldConfig');
    $mock_entity->expects($this->exactly(2))
      ->method('getEntityTypeId')
      ->willReturn('mock_entity');
    $mock_entity->expects($this->once())
      ->method('bundle')
      ->willReturn('mock_bundle');
    $mock_entity->expects($this->once())
      ->method('getTargetEntityTypeId')
      ->willReturn('target_type');
    $mock_entity->expects($this->once())
      ->method('getTargetBundle')
      ->willReturn('target_bundle');
    require_once __DIR__ . '/../../../handy_cache_tags.module';
    _handy_cache_tags_clear_entity_tags($mock_entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewContainer() {
    $container = parent::getNewContainer();
    $cache_handler = new HandyCacheTagsHandler($container->get('handy_cache_tags.manager'));
    $container->set('handy_cache_tags.handler', $cache_handler);
    return $container;
  }

}
