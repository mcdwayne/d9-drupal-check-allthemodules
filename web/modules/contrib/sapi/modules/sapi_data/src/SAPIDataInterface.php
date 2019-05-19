<?php

namespace Drupal\sapi_data;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Statistics API Data entry entities.
 *
 * @ingroup sapi_data
 */
interface SAPIDataInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Statistics API Data entry type.
   *
   * @return string
   *   The Statistics API Data entry type.
   */
  public function getType();

  /**
   * Gets the Statistics API Data entry name.
   *
   * @return string
   *   Name of the Statistics API Data entry.
   */
  public function getName();

  /**
   * Sets the Statistics API Data entry name.
   *
   * @param string $name
   *   The Statistics API Data entry name.
   *
   * @return \Drupal\sapi_data\SAPIDataInterface
   *   The called Statistics API Data entry entity.
   */
  public function setName($name);

  /**
   * Gets the Statistics API Data entry creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Statistics API Data entry.
   */
  public function getCreatedTime();

  /**
   * Sets the Statistics API Data entry creation timestamp.
   *
   * @param int $timestamp
   *   The Statistics API Data entry creation timestamp.
   *
   * @return \Drupal\sapi_data\SAPIDataInterface
   *   The called Statistics API Data entry entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Statistics API Data entry published status indicator.
   *
   * Unpublished Statistics API Data entry are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Statistics API Data entry is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Statistics API Data entry.
   *
   * @param bool $published
   *   TRUE to set this Statistics API Data entry to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\sapi_data\SAPIDataInterface
   *   The called Statistics API Data entry entity.
   */
  public function setPublished($published);

}
