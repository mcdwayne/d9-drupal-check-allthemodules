<?php

namespace Drupal\onepass;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines a Controller class for OnepassNode entities.
 */
class OnepassNodeStorage extends SqlContentEntityStorage implements OnepassNodeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByNid($nid) {
    $entities = $this->loadByProperties(array('nid' => $nid));
    return $entities ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function saveRelation($nid) {
    if (!$this->loadByNid($nid)) {
      $entity = $this->create(array('nid' => $nid));
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRelation($nid) {
    if ($entity = $this->loadByNid($nid)) {
      $this->delete(array($entity));
    }
  }

}
