<?php

namespace Drupal\linky;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining managed link entities.
 *
 * @ingroup linky
 */
interface LinkyInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Linky creation timestamp.
   *
   * @return int
   *   Creation timestamp of the managed link.
   */
  public function getCreatedTime();

  /**
   * Sets the managed link creation timestamp.
   *
   * @param int $timestamp
   *   The Linky creation timestamp.
   *
   * @return \Drupal\linky\LinkyInterface
   *   The called Linky entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the last checked timestamp.
   *
   * @return int
   *   Last checked timestamp of the managed link.
   */
  public function getLastCheckedTime();

  /**
   * Sets the last checked timestamp.
   *
   * @param int $timestamp
   *   The Linky last checked timestamp.
   *
   * @return \Drupal\linky\LinkyInterface
   *   The called Linky entity.
   */
  public function setLastCheckedTime($timestamp);

}
