<?php

namespace Drupal\contacts_dbs;

use Drupal\contacts_dbs\Entity\DBSStatus;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for dbs status items.
 */
class DBSStatusStorage extends SqlContentEntityStorage implements DBSStatusStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(DBSStatus $status) {
    $query = $this->getQuery()
      ->allRevisions()
      ->condition('id', $status->id())
      ->sort($this->revisionKey, 'DESC');

    return array_keys($query->execute());
  }

}
