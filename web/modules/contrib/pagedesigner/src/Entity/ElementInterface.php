<?php

namespace Drupal\pagedesigner\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Pagedesigner Element entities.
 *
 * @ingroup pagedesigner
 */
interface ElementInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Pagedesigner Element name.
   *
   * @return string
   *   Name of the Pagedesigner Element.
   */
  public function getName();

  /**
   * Sets the Pagedesigner Element name.
   *
   * @param string $name
   *   The Pagedesigner Element name.
   *
   * @return \Drupal\pagedesigner\Entity\ElementInterface
   *   The called Pagedesigner Element entity.
   */
  public function setName($name);

  /**
   * Gets the Pagedesigner Element creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Pagedesigner Element.
   */
  public function getCreatedTime();

  /**
   * Sets the Pagedesigner Element creation timestamp.
   *
   * @param int $timestamp
   *   The Pagedesigner Element creation timestamp.
   *
   * @return \Drupal\pagedesigner\Entity\ElementInterface
   *   The called Pagedesigner Element entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Pagedesigner Element published status indicator.
   *
   * Unpublished Pagedesigner Element are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Pagedesigner Element is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Pagedesigner Element.
   *
   * @param bool $published
   *   TRUE to set this Pagedesigner Element to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\pagedesigner\Entity\ElementInterface
   *   The called Pagedesigner Element entity.
   */
  public function setPublished($published);

  /**
   * Gets the Pagedesigner Element revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Pagedesigner Element revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\pagedesigner\Entity\ElementInterface
   *   The called Pagedesigner Element entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Pagedesigner Element revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Pagedesigner Element revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\pagedesigner\Entity\ElementInterface
   *   The called Pagedesigner Element entity.
   */
  public function setRevisionUserId($uid);

}
