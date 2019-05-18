<?php

namespace Drupal\advertising_products;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Advertising Product entities.
 *
 * @ingroup advertising_products
 */
interface AdvertisingProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Advertising Product type.
   *
   * @return string
   *   The Advertising Product type.
   */
  public function getType();

  /**
   * Gets the Advertising Product name.
   *
   * @return string
   *   Name of the Advertising Product.
   */
  public function getName();

  /**
   * Sets the Advertising Product name.
   *
   * @param string $name
   *   The Advertising Product name.
   *
   * @return \Drupal\advertising_products\AdvertisingProductInterface
   *   The called Advertising Product entity.
   */
  public function setName($name);

  /**
   * Gets the Advertising Product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Advertising Product.
   */
  public function getCreatedTime();

  /**
   * Sets the Advertising Product creation timestamp.
   *
   * @param int $timestamp
   *   The Advertising Product creation timestamp.
   *
   * @return \Drupal\advertising_products\AdvertisingProductInterface
   *   The called Advertising Product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Advertising Product published status indicator.
   *
   * Unpublished Advertising Product are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Advertising Product is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Advertising Product.
   *
   * @param bool $published
   *   TRUE to set this Advertising Product to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\advertising_products\AdvertisingProductInterface
   *   The called Advertising Product entity.
   */
  public function setPublished($published);

}
