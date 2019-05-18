<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface for interacting with the status of an entity.
 */
interface EntityActiveInterface {

  /**
   * Returns TRUE if the entity is active.
   *
   * @return bool
   *   TRUE if the entity is active, false otherwise.
   */
  public function isActive();

  /**
   * Returns TRUE if the entity is inactive.
   *
   * @return bool
   *   TRUE if the entity is inactive, false otherwise.
   */
  public function isInactive();

  /**
   * Activates the entity.
   *
   * @return \Drupal\core_extend\Entity\EntityActiveInterface
   *   The called entity.
   */
  public function activate();

  /**
   * Deactivates the entity.
   *
   * @return \Drupal\core_extend\Entity\EntityActiveInterface
   *   The called entity.
   */
  public function inactivate();

}
