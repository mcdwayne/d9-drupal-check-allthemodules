<?php

namespace Drupal\dmt\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Module entities.
 *
 * @ingroup dmt
 */
interface ModuleInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Module name.
   *
   * @return string
   *   Name of the Module.
   */
  public function getName();

  /**
   * Sets the Module name.
   *
   * @param string $name
   *   The Module name.
   *
   * @return \Drupal\dmt\Entity\ModuleInterface
   *   The called Module entity.
   */
  public function setName($name);

  /**
   * Gets the Module creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Module.
   */
  public function getCreatedTime();

  /**
   * Sets the Module creation timestamp.
   *
   * @param int $timestamp
   *   The Module creation timestamp.
   *
   * @return \Drupal\dmt\Entity\ModuleInterface
   *   The called Module entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Module published status indicator.
   *
   * Unpublished Module are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Module is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Module.
   *
   * @param bool $published
   *   TRUE to set this Module to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dmt\Entity\ModuleInterface
   *   The called Module entity.
   */
  public function setPublished($published);

}
