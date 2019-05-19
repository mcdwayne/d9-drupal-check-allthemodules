<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Widget Extend entities.
 *
 * @ingroup stacks
 */
interface WidgetExtendEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Widget Extend entity title.
   *
   * @return string
   *   Title of the Widget Extend entity.
   */
  public function getTitle();

  /**
   * Sets the Widget Extend entity title.
   */
  public function setTitle($title);

  /**
   * Gets the Widget label.
   *
   * @return string
   *   The Widget label
   */
  public function label();

  /**
   * Gets the Widget Extend type.
   *
   * @return string
   *   The Widget Extend type.
   */
  public function getType();

  /**
   * Gets the Widget Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Widget Extend.
   */
  public function getCreatedTime();

  /**
   * Sets the Widget Extend creation timestamp.
   *
   * @param int $timestamp
   *   The Widget Extend creation timestamp.
   */
  public function setCreatedTime($timestamp);

}
