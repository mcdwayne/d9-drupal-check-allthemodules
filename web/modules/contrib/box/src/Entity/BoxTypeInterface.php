<?php

namespace Drupal\box\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining Box type entities.
 */
interface BoxTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this box type.
   */
  public function getDescription();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Checks whether the 'Revision log message' fields should be required.
   *
   * @return bool
   *   TRUE if the field should be required, FALSE otherwise.
   */
  public function isRevisionLogRequired();

  /**
   * Require 'Revision log message'.
   */
  public function setRevisionLogRequired();

  /**
   * Do not require 'Revision log message'
   */
  public function setRevisionLogOptional();
}
