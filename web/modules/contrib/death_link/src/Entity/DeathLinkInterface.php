<?php

namespace Drupal\death_link\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Death Link entities.
 */
interface DeathLinkInterface extends ConfigEntityInterface {

  /**
   * Get the path to redirect.
   *
   * @return string
   *   A string that represents a path alias.
   */
  public function getFromUri();

  /**
   * Get the path to redirect to.
   *
   * @return string
   *   A string that represents a path alias.
   */
  public function getToUri();

  /**
   * Get the entity to redirect to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getToEntity();

}
