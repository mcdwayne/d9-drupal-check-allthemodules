<?php

namespace Drupal\Tests\handy_cache_tags\Unit;

use Drupal\handy_cache_tags\HandyCacheTagsManager;
use Drupal\Tests\PhpunitCompatibilityTrait;

/**
 * Test tag name generation.
 *
 * @group handy_cache_tags
 */
class TagNamesTest extends HandyCacheTagsBase {

  use PhpunitCompatibilityTrait;

  /**
   * Tests procedural with no mocking.
   */
  public function testProcedural() {
    require_once __DIR__ . '/../../../handy_cache_tags.module';
    $container = $this->getNewContainer();
    \Drupal::setContainer($container);
    $mock_entity = $this->getMockEntity(TRUE);
    $mock_entity->expects($this->exactly(3))
      ->method('getEntityTypeId')
      ->willReturn('mock_entity');
    $mock_entity->expects($this->exactly(2))
      ->method('bundle')
      ->willReturn('mock_bundle');
    $this->assertArrayEquals([
      'handy_cache_tags:mock_entity',
      'handy_cache_tags:mock_entity:mock_bundle',
    ], handy_cache_tags_get_entity_tags($mock_entity));
    $this->assertEquals('handy_cache_tags:node', handy_cache_tags_get_tag('node'));
    $this->assertEquals('handy_cache_tags:mock_entity:mock_bundle', handy_cache_tags_get_bundle_tag_from_entity($mock_entity));
    $this->assertEquals('handy_cache_tags:node:article', handy_cache_tags_get_bundle_tag('node', 'article'));
  }

  /**
   * Test oop class name thing.
   */
  public function testHandler() {
    $tag_manager = new HandyCacheTagsManager();
    $mock_entity = $this->getMockEntity();
    $this->assertArrayEquals([
      'handy_cache_tags:mock_entity',
      'handy_cache_tags:mock_entity:mock_bundle',
    ], $tag_manager->getEntityTags($mock_entity));
    $this->assertEquals('handy_cache_tags:node', $tag_manager->getTag('node'));
    $this->assertEquals('handy_cache_tags:node:article', $tag_manager->getBundleTag('node', 'article'));
  }

}
