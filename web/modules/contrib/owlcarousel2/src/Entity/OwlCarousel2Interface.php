<?php

namespace Drupal\owlcarousel2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OwlCarousel2 entities.
 *
 * @ingroup owlcarousel2
 */
interface OwlCarousel2Interface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the OwlCarousel2 name.
   *
   * @return string
   *   Name of the OwlCarousel2.
   */
  public function getName();

  /**
   * Sets the OwlCarousel2 name.
   *
   * @param string $name
   *   The OwlCarousel2 name.
   *
   * @return \Drupal\owlcarousel2\Entity\OwlCarousel2Interface
   *   The called OwlCarousel2 entity.
   */
  public function setName($name);

  /**
   * Gets the OwlCarousel2 creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OwlCarousel2.
   */
  public function getCreatedTime();

  /**
   * Sets the OwlCarousel2 creation timestamp.
   *
   * @param int $timestamp
   *   The OwlCarousel2 creation timestamp.
   *
   * @return \Drupal\owlcarousel2\Entity\OwlCarousel2Interface
   *   The called OwlCarousel2 entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the OwlCarousel2 published status indicator.
   *
   * Unpublished OwlCarousel2 are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the OwlCarousel2 is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a OwlCarousel2.
   *
   * @param bool $published
   *   TRUE to set this OwlCarousel2 to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\owlcarousel2\Entity\OwlCarousel2Interface
   *   The called OwlCarousel2 entity.
   */
  public function setPublished($published);

  /**
   * Gets the OwlCarousel2 revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the OwlCarousel2 revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\owlcarousel2\Entity\OwlCarousel2Interface
   *   The called OwlCarousel2 entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the OwlCarousel2 revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the OwlCarousel2 revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\owlcarousel2\Entity\OwlCarousel2Interface
   *   The called OwlCarousel2 entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Return all config items from the Carousel.
   *
   * @return array
   *   Array of carousel items.
   */
  public function getItems();

}
