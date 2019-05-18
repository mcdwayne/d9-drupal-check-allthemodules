<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\comment\CommentStorage as CoreCommentStorage;

/**
 * Storage handler for comments.
 */
class CommentStorage extends CoreCommentStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait {
    delete as deleteEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    // Ensure that the entities are keyed by ID.
    $keyed_entities = [];
    foreach ($entities as $entity) {
      $keyed_entities[$entity->id()] = $entity;
    }

    // Delete received comments and all their children.
    if (!empty($keyed_entities)) {
      $child_cids = $this->getChildCids($keyed_entities);
      while (!empty($child_cids)) {
        $child_entities = $this->loadMultiple($child_cids);
        $keyed_entities = $keyed_entities + $child_entities;
        $child_cids = $this->getChildCids($child_entities);
      }
    }
    // Sort the array with entities descending to delete children before their
    // parents.
    krsort($keyed_entities);
    $this->deleteEntities($keyed_entities);
  }

}
