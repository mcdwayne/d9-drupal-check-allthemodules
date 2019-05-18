<?php

namespace Drupal\menu_megadrop\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Menu megadrop entities.
 *
 * @ingroup menu_megadrop
 */
interface MenuMegadropInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Menu megadrop name.
   *
   * @return string
   *   Name of the Menu megadrop.
   */
  public function getName();

  /**
   * Sets the Menu megadrop name.
   *
   * @param string $name
   *   The Menu megadrop name.
   *
   * @return \Drupal\menu_megadrop\Entity\MenuMegadropInterface
   *   The called Menu megadrop entity.
   */
  public function setName($name);

  /**
   * Gets the Menu megadrop creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Menu megadrop.
   */
  public function getCreatedTime();

  /**
   * Sets the Menu megadrop creation timestamp.
   *
   * @param int $timestamp
   *   The Menu megadrop creation timestamp.
   *
   * @return \Drupal\menu_megadrop\Entity\MenuMegadropInterface
   *   The called Menu megadrop entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Menu megadrop published status indicator.
   *
   * Unpublished Menu megadrop are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Menu megadrop is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Menu megadrop.
   *
   * @param bool $published
   *   TRUE to set this Menu megadrop to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\menu_megadrop\Entity\MenuMegadropInterface
   *   The called Menu megadrop entity.
   */
  public function setPublished($published);

}
