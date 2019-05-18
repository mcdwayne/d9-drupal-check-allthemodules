<?php

namespace Drupal\entity_generic\Entity;

/**
 * Provides an interface defining a type entity.
 */
interface GenericTypeInterface extends GenericConfigInterface {

  /**
   * Determines whether the entity type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this entity type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this entity type.
   */
  public function getDescription();

  /**
   * Gets whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function isNewRevision();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

}
