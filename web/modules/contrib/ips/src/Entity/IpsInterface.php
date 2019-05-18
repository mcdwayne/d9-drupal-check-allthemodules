<?php

namespace Drupal\ips\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ips entities.
 *
 * @ingroup ips
 */
interface IpsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  // IP not bind to specific server or cabinet or switch.
  const IP_STATUS_UNBIND   = 0;
  // IP bind to specific server.
  const IP_STATUS_BIND   = 1;
  // IP not bind to anything.
  const IP_STATUS_UNUSED = 2;
  // IP has sale to customer.
  const IP_STATUS_USED   = 3;
  /**
   * Gets the Ips name.
   *
   * @return string
   *   Name of the Ips.
   */
  public function getName();

  /**
   * Sets the Ips name.
   *
   * @param string $name
   *   The Ips name.
   *
   * @return \Drupal\ips\Entity\IpsInterface
   *   The called Ips entity.
   */
  public function setName($name);

  /**
   * Gets the Ips creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ips.
   */
  public function getCreatedTime();

  /**
   * Sets the Ips creation timestamp.
   *
   * @param int $timestamp
   *   The Ips creation timestamp.
   *
   * @return \Drupal\ips\Entity\IpsInterface
   *   The called Ips entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Ips published status indicator.
   *
   * Unpublished Ips are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Ips is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Ips.
   *
   * @param bool $published
   *   TRUE to set this Ips to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ips\Entity\IpsInterface
   *   The called Ips entity.
   */
  public function setPublished($published);

  /**
   * Gets the Ips revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Ips revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ips\Entity\IpsInterface
   *   The called Ips entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Ips revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Ips revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ips\Entity\IpsInterface
   *   The called Ips entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the ips status.
   * @return int
   */
  public function getStatus();

  /**
   * Sets ips status.
   *
   * @param int $status
   *
   * @return mixed
   */
  public function setStatus($status);

}
