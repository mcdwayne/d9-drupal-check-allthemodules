<?php

namespace Drupal\hidden_tab\Entity\Base;

use Drupal\Core\Entity\EntityInterface;

/**
 * An entity who maintains a boolean flag as it's enabled disabled status.
 */
interface StatusedEntityInterface extends EntityInterface {

  /**
   * Whether the entity is enabled or not.
   *
   * @return bool
   *   True to enable entity, FALSE to disable.
   */
  public function isEnabled(): bool;

  /**
   * Set status of the entity to enabled (true).
   */
  public function enable();

}
