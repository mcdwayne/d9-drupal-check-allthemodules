<?php

namespace Drupal\sponsor\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Sponsor entities.
 *
 * @ingroup sponsor
 */
interface SponsorInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Sponsor name.
   *
   * @return string
   *   Name of the Sponsor.
   */
  public function getName();

  /**
   * Sets the Sponsor name.
   *
   * @param string $name
   *   The Sponsor name.
   *
   * @return \Drupal\sponsor\Entity\SponsorInterface
   *   The called Sponsor entity.
   */
  public function setName($name);

  /**
   * Gets the Sponsor creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Sponsor.
   */
  public function getCreatedTime();

  /**
   * Sets the Sponsor creation timestamp.
   *
   * @param int $timestamp
   *   The Sponsor creation timestamp.
   *
   * @return \Drupal\sponsor\Entity\SponsorInterface
   *   The called Sponsor entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Sponsor published status indicator.
   *
   * Unpublished Sponsor are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Sponsor is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Sponsor.
   *
   * @param bool $published
   *   TRUE to set this Sponsor to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\sponsor\Entity\SponsorInterface
   *   The called Sponsor entity.
   */
  public function setPublished($published);

  /**
   * Gets the Sponsor revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Sponsor revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\sponsor\Entity\SponsorInterface
   *   The called Sponsor entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Sponsor revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Sponsor revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\sponsor\Entity\SponsorInterface
   *   The called Sponsor entity.
   */
  public function setRevisionUserId($uid);

}
