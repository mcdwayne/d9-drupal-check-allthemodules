<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for quick_codes.
 */
class QuickCodeStorage extends SqlContentEntityStorage implements QuickCodeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadParents($qid) {
    $parents = [];

    if ($entity = $this->load($qid)) {
      while($parent = $entity->parent->entity) {
        $entity = $parent;
        $parents[$entity->id()] = $entity;
      }
    }

    return $parents;
  }

}
