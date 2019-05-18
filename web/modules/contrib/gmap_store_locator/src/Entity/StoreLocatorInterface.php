<?php

namespace Drupal\store_locator\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Store locator entities.
 *
 * @ingroup store_locator
 */
interface StoreLocatorInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Store locator name.
   *
   * @return string
   *   Name of the Store locator.
   */
  public function getName();

  /**
   * Sets the Store locator name.
   *
   * @param string $name
   *   The Store locator name.
   *
   * @return string
   *   \Drupal\store_locator\Entity\StoreLocatorInterface called
   *   Store locator entity.
   */
  public function setName($name);

  /**
   * Gets the Store locator creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Store locator.
   */
  public function getCreatedTime();

  /**
   * Sets the Store locator creation timestamp.
   *
   * @param int $timestamp
   *   The Store locator creation timestamp.
   *
   * @return string
   *   \Drupal\store_locator\Entity\StoreLocatorInterface
   *   called Store locator entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Store locator published status indicator.
   *
   * Unpublished Store locator are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Store locator is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Store locator.
   *
   * @param bool $published
   *   TRUE to set this Store locator to published,
   *          FALSE to set it to unpublished.
   *
   * @return string
   *   \Drupal\store_locator\Entity\StoreLocatorInterface
   *   Called Store locator entity.
   */
  public function setPublished($published);

}
