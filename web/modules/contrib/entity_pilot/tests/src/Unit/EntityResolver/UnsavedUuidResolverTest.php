<?php

namespace Drupal\Tests\entity_pilot\Unit\EntityResolver;

use Drupal\entity_pilot\EntityResolver\UnsavedUuidResolver;
use Drupal\Tests\UnitTestCase;

/**
 * Tests unsaved resolver.
 *
 * @group entity_pilot
 * @coversDefaultClass \Drupal\entity_pilot\EntityResolver\UnsavedUuidResolver
 */
class UnsavedUuidResolverTest extends UnitTestCase {

  /**
   * Normalizer.
   *
   * @var \Drupal\serialization\EntityResolver\UuidReferenceInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $normalizer;

  /**
   * Mock node.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * Exists plugin manager.
   *
   * @var \Drupal\entity_pilot\ExistsPluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $existsPluginManager;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * Resolver mock.
   *
   * @var \Drupal\entity_pilot\EntityResolver\UnsavedUuidResolver|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuidResolver;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    $uuid = ['value' => 'ponies-ponies-ra-ra-ra'];
    $this->normalizer = $this->createMock('\Drupal\serialization\EntityResolver\UuidReferenceInterface');
    $this->normalizer->expects($this->any())
      ->method('getUuid')
      ->willReturn($uuid['value']);
    $this->existsPluginManager = $this->createMock('\Drupal\entity_pilot\ExistsPluginManagerInterface');
    $this->entityManager = $this->createMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->uuidResolver = new UnsavedUuidResolver($this->existsPluginManager, $this->entityManager);
    $this->entity = $this->createMock('\Drupal\node\NodeInterface');
    $this->entity->expects($this->any())
      ->method('uuid')
      ->willReturn($uuid['value']);
    $this->existsPluginManager->expects($this->at(0))
      ->method('exists')
      ->willReturn(FALSE);
    $this->existsPluginManager->expects($this->at(1))
      ->method('exists')
      ->willReturn('who likes ponies');
  }

  /**
   * Tests resolver functionality.
   *
   * @covers ::add
   * @covers ::resolve
   * @covers ::__construct
   */
  public function testResolver() {
    // First call, nothing in the stack - will return NULL.
    $this->assertNull($this->uuidResolver->resolve($this->normalizer, [], 'pony'));
    $this->uuidResolver->add($this->entity);
    // Second call, item in the stack but no existing match.
    $this->assertEquals($this->uuidResolver->resolve($this->normalizer, [], 'pony'), $this->entity);
    // Third call, returns existing item.
    $this->assertEquals($this->uuidResolver->resolve($this->normalizer, [], 'pony'), 'who likes ponies');
  }

}
