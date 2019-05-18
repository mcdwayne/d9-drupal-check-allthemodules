<?php

namespace Drupal\record;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a record type entity.
 */
interface RecordTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this record type.
   */
  public function getDescription();

}
