<?php

namespace Drupal\contacts_dbs;

use Drupal\contacts_dbs\Entity\DBSStatus;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for dbs_status entity storage classes.
 */
interface DBSStatusStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of all revision ids for a given DBS Status.
   *
   * @param \Drupal\contacts_dbs\Entity\DBSStatus $status
   *   The DBS Status entity being looked up.
   *
   * @return array
   *   Array of revision ids.
   */
  public function revisionIds(DBSStatus $status);

}
