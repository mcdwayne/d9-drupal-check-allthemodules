<?php

namespace Drupal\erf\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Participant entities.
 *
 * @ingroup erf
 */
interface ParticipantInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Participant creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Participant.
   */
  public function getCreatedTime();

  /**
   * Sets the Participant creation timestamp.
   *
   * @param int $timestamp
   *   The Participant creation timestamp.
   *
   * @return \Drupal\erf\Entity\ParticipantInterface
   *   The called Participant entity.
   */
  public function setCreatedTime($timestamp);

}
