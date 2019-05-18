<?php

namespace Drupal\monster_menus\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining MM Page entities.
 *
 * @ingroup monster_menus
 */
interface MMTreeInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the MM Page name.
   *
   * @return string
   *   Name of the MM Page.
   */
  public function getName();

  /**
   * Sets the MM Page name.
   *
   * @param string $name
   *   The MM Page name.
   *
   * @return \Drupal\monster_menus\Entity\MMTreeInterface
   *   The called MM Page entity.
   */
  public function setName($name);

  /**
   * Gets the MM Page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the MM Page.
   */
  public function getCreatedTime();

  /**
   * Sets the MM Page creation timestamp.
   *
   * @param int $timestamp
   *   The MM Page creation timestamp.
   *
   * @return \Drupal\monster_menus\Entity\MMTreeInterface
   *   The called MM Page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the MM Page modification timestamp.
   *
   * @return int
   *   Modification timestamp of the MM Page.
   */
  public function getChangedTime();

  /**
   * Sets the MM Page modification timestamp.
   *
   * @param int $timestamp
   *   The MM Page modification timestamp.
   *
   * @return \Drupal\monster_menus\Entity\MMTreeInterface
   *   The called MM Page entity.
   */
  public function setChangedTime($timestamp);

  /**
   * Determine if the tree entry is a group.
   *
   * @return bool
   *   TRUE if the entry is a group.
   */
  public function isGroup();

}
