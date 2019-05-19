<?php

declare(strict_types=1);

namespace Drupal\testtools;

use Drupal\Core\Entity\EntityInterface;

/**
 * A trait that cleans up entities after the test.
 *
 * Useful when working with remote entities.
 */
trait EntityCleanupTrait {

  /**
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * Marks an entity to be deleted in the teardown.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function markForCleanup(EntityInterface $entity): void {
    $this->entities[] = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    foreach ($this->entities as $entity) {
      try {
        $entity->delete();
      }
      catch (\Exception $ex) {}
    }

    parent::tearDown();
  }
}
