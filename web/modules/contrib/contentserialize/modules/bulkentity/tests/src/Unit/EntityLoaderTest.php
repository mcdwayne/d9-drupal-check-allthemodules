<?php

namespace Drupal\Tests\bulkentity\Unit;

use Drupal\bulkentity\EntityLoader;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EntityLoader.
 *
 * @coversDefaultClass \Drupal\bulkentity\EntityLoader
 *
 * @group bulkentity
 */
class EntityLoaderTest extends UnitTestCase {

  /**
   * Test byIds correctly batches and resets the static cache.
   *
   * @covers ::byIds
   */
  public function testByIds() {
    // This might turn out to be a bit brittle by its reliance on $this->at()
    // but it does sanity-check the batching and static cache reset.
    $storage_mock = $this->createMock(EntityStorageInterface::class);
    $storage_mock->expects($this->at(0))
      ->method('loadMultiple')
      ->willReturn([new \stdClass(), new \stdClass(), new \stdClass()]);
    $storage_mock->expects($this->at(1))
      ->method('resetCache');
    $storage_mock->expects($this->at(2))
      ->method('loadMultiple')
      ->willReturn([new \stdClass(), new \stdClass(), new \stdClass()]);
    $storage_mock->expects($this->at(3))
      ->method('resetCache');
    $storage_mock->expects($this->at(4))
      ->method('loadMultiple')
      ->willReturn([new \stdClass()]);
    $storage_mock->expects($this->at(5))
      ->method('resetCache');

    $entity_type_manager_mock = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager_mock->method('getStorage')
      ->willReturn($storage_mock);

    $loader = new EntityLoader($entity_type_manager_mock);

    foreach ($loader->byIds(3, range(1, 7), 'node') as $node) {};
  }

}