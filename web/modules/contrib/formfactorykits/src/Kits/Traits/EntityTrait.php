<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait EntityTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait EntityTrait {
  /**
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public function getEntityTypeManagerService() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $service */
    static $service;
    if (NULL === $service) {
      $service = $this->kitsService->getContainer()
        ->get('entity_type.manager');
    }
    return $service;
  }

  /**
   * @param string $entityType
   * @param string $conjunction
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  public function getEntityQuery($entityType, $conjunction = 'AND') {
    return $this->getEntityTypeManagerService()
      ->getStorage($entityType)
      ->getQuery($conjunction);
  }
}
