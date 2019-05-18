<?php

namespace Drupal\role_watchdog\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Role Watchdog entities.
 *
 * @ingroup role_watchdog
 */
interface RoleWatchdogInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Role Watchdog name.
   *
   * @return string
   *   Name of the Role Watchdog.
   */
  public function getName();

  /**
   * Sets the Role Watchdog name.
   *
   * @param string $name
   *   The Role Watchdog name.
   *
   * @return \Drupal\role_watchdog\Entity\RoleWatchdogInterface
   *   The called Role Watchdog entity.
   */
  public function setName($name);

  /**
   * Gets the Role Watchdog creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Role Watchdog.
   */
  public function getCreatedTime();

  /**
   * Sets the Role Watchdog creation timestamp.
   *
   * @param int $timestamp
   *   The Role Watchdog creation timestamp.
   *
   * @return \Drupal\role_watchdog\Entity\RoleWatchdogInterface
   *   The called Role Watchdog entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Role Watchdog published status indicator.
   *
   * Unpublished Role Watchdog are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Role Watchdog is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Role Watchdog.
   *
   * @param bool $published
   *   TRUE to set this Role Watchdog to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\role_watchdog\Entity\RoleWatchdogInterface
   *   The called Role Watchdog entity.
   */
  public function setPublished($published);

}
