<?php

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;

/**
 * Provides assertions for file usage.
 */
trait AssertFileUsageTrait {

  /**
   * Assert file usage.
   *
   * @param int $expectedUsage
   *   The expected usage count.
   * @param int $fid
   *   The file ID to check for usage.
   * @param string $module
   *   The module to check for usage.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The attached entity to check for usage.
   */
  protected function assertFileUsage($expectedUsage, $fid, $module, EntityInterface $entity) {
    $file = File::load($fid);
    $usage = \Drupal::service('file.usage')->listUsage($file);
    $usage = isset($usage[$module][$entity->getEntityTypeId()][$entity->id()]) ? $usage[$module][$entity->getEntityTypeId()][$entity->id()] : 0;
    $this->assertEquals($expectedUsage, $usage);
  }

}