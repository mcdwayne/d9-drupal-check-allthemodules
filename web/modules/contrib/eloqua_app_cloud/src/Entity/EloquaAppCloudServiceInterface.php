<?php

namespace Drupal\eloqua_app_cloud\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Eloqua AppCloud Service entities.
 *
 * @ingroup eloqua_app_cloud
 */
interface EloquaAppCloudServiceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Eloqua AppCloud Service type.
   *
   * @return string
   *   The Eloqua AppCloud Service type.
   */
  public function getType();

  /**
   * Gets the Eloqua AppCloud Service name.
   *
   * @return string
   *   Name of the Eloqua AppCloud Service.
   */
  public function getName();

  /**
   * Sets the Eloqua AppCloud Service name.
   *
   * @param string $name
   *   The Eloqua AppCloud Service name.
   *
   * @return \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceInterface
   *   The called Eloqua AppCloud Service entity.
   */
  public function setName($name);

  /**
   * Gets the Eloqua AppCloud Service creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Eloqua AppCloud Service.
   */
  public function getCreatedTime();

  /**
   * Sets the Eloqua AppCloud Service creation timestamp.
   *
   * @param int $timestamp
   *   The Eloqua AppCloud Service creation timestamp.
   *
   * @return \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceInterface
   *   The called Eloqua AppCloud Service entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Eloqua AppCloud Service published status indicator.
   *
   * Unpublished Eloqua AppCloud Service are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Eloqua AppCloud Service is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Eloqua AppCloud Service.
   *
   * @param bool $published
   *   TRUE to set this Eloqua AppCloud Service to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceInterface
   *   The called Eloqua AppCloud Service entity.
   */
  public function setPublished($published);

}
