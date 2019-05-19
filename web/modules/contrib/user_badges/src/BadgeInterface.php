<?php

/**
 * @file
 * Contains \Drupal\user_badges\BadgeInterface.
 */

namespace Drupal\user_badges;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Badge entities.
 *
 * @ingroup user_badges
 */
interface BadgeInterface extends ContentEntityInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Badge type.
   *
   * @return string
   *   The Badge type.
   */
  public function getType();

  /**
   * Gets the Badge name.
   *
   * @return string
   *   Name of the Badge.
   */
  public function getName();

  /**
   * Sets the Badge name.
   *
   * @param string $name
   *   The Badge name.
   *
   * @return \Drupal\user_badges\BadgeInterface
   *   The called Badge entity.
   */
  public function setName($name);

  /**
   * Gets the Badge weight.
   *
   * @return integer
   *   Weight of the Badge.
   */
  public function getBadgeWeight();

  /**
   * Sets the Badge weight.
   *
   * @param string $weight
   *   The Badge weight.
   *
   * @return \Drupal\user_badges\BadgeInterface
   *   The called Badge entity.
   */
  public function setBadgeWeight($weight);

  /**
   * Gets the Role id associated with Badge.
   *
   * @return integer
   *   Role id of role associated with Badge
   */
  public function getBadgeRoleIds();

  /**
   * Sets Role id of Badge.
   *
   * @param integer $rid
   *   Role id of Role
   *
   * @return \Drupal\user_badges\BadgeInterface
   *   The called Badge entity.
   */
  public function setBadgeRoleId($rid);

}
