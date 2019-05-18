<?php

namespace Drupal\real_estate_property\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Property entities.
 *
 * @ingroup real_estate_property
 */
interface PropertyInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Property type.
   *
   * @return string
   *   The Property type.
   */
  public function getType();

  /**
   * Gets the Property title.
   *
   * @return string
   *   Name of the Property.
   */
  public function getTitle();

  /**
   * Sets the Property title.
   *
   * @param string $title
   *   The Property title.
   *
   * @return \Drupal\real_estate_property\Entity\PropertyInterface
   *   The called Property entity.
   */
  public function setTitle($title);

  /**
   * Gets the Property creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Property.
   */
  public function getCreatedTime();

  /**
   * Sets the Property creation timestamp.
   *
   * @param int $timestamp
   *   The Property creation timestamp.
   *
   * @return \Drupal\real_estate_property\Entity\PropertyInterface
   *   The called Property entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Property published status indicator.
   *
   * Unpublished Property are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Property is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Property.
   *
   * @param bool $published
   *   TRUE to set this Property to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\real_estate_property\Entity\PropertyInterface
   *   The called Property entity.
   */
  public function setPublished($published);

}
