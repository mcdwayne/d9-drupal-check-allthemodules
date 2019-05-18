<?php

namespace Drupal\invite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Invite entities.
 *
 * @ingroup invite
 */
interface InviteInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Invite creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Invite.
   */
  public function getCreatedTime();

  /**
   * Sets the Invite creation timestamp.
   *
   * @param int $timestamp
   *   The Invite creation timestamp.
   *
   * @return \Drupal\invite\InviteInterface
   *   The called Invite entity.
   */
  public function setCreatedTime($timestamp);

}
