<?php

namespace Drupal\competition;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Competition entries.
 *
 * @ingroup competition
 */
interface CompetitionEntryInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Competition entry status - Created.
   */
  const STATUS_CREATED = 0x00;

  /**
   * Competition entry status - Updated.
   */
  const STATUS_UPDATED = 0x01;

  /**
   * Competition entry status - Finalized.
   */
  const STATUS_FINALIZED = 0x02;

  /**
   * Competition entry status - Archived.
   */
  const STATUS_ARCHIVED = 0x03;

  /**
   * Gets the bundle name, which is a Competition entity's ID.
   *
   * @return string
   *   The Competition entity ID
   */
  public function getType();

  /**
   * Gets the Competition to which this Entry is associated.
   *
   * @return \Drupal\competition\CompetitionInterface
   *   The Competition entity
   */
  public function getCompetition();

  /**
   * Gets the Competition entry cycle.
   *
   * @return string
   *   The Competition cycle.
   */
  public function getCycle();

  /**
   * Gets the Competition entry status.
   *
   * @return string
   *   Status of the Competition entry.
   */
  public function getStatus();

  /**
   * Gets the Competition entry creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Competition entry.
   */
  public function getCreatedTime();

  /**
   * Sets the Competition entry creation timestamp.
   *
   * @param int $timestamp
   *   The Competition entry creation timestamp.
   *
   * @return \Drupal\competition\CompetitionEntryInterface
   *   The called Competition entry entity.
   */
  public function setCreatedTime($timestamp);

}
