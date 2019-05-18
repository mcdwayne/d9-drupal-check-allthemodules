<?php

namespace Drupal\flipping_book\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Flipping Book entities.
 *
 * @ingroup flipping_book
 */
interface FlippingBookInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Flipping Book type.
   *
   * @return string
   *   The Flipping Book type.
   */
  public function getType();

  /**
   * Gets the Flipping Book name.
   *
   * @return string
   *   Name of the Flipping Book.
   */
  public function getName();

  /**
   * Sets the Flipping Book name.
   *
   * @param string $name
   *   The Flipping Book name.
   *
   * @return \Drupal\flipping_book\Entity\FlippingBookInterface
   *   The called Flipping Book entity.
   */
  public function setName($name);

  /**
   * Gets the Flipping Book creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Flipping Book.
   */
  public function getCreatedTime();

  /**
   * Sets the Flipping Book creation timestamp.
   *
   * @param int $timestamp
   *   The Flipping Book creation timestamp.
   *
   * @return \Drupal\flipping_book\Entity\FlippingBookInterface
   *   The called Flipping Book entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Flipping Book published status indicator.
   *
   * Unpublished Flipping Book are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Flipping Book is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Flipping Book.
   *
   * @param bool $published
   *   TRUE to set this Flipping Book to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\flipping_book\Entity\FlippingBookInterface
   *   The called Flipping Book entity.
   */
  public function setPublished($published);

  /**
   * Gets the Flipping Book directory path.
   *
   * @return string
   *   Directory path of the Flipping Book.
   */
  public function getDirectory();

  /**
   * Sets the Flipping Book directory path.
   *
   * @param string $path
   *   The Flipping Book directory path.
   *
   * @return \Drupal\flipping_book\Entity\FlippingBookInterface
   *   The called Flipping Book entity.
   */
  public function setDirectory($path);

}
