<?php

/**
 * @file
 * Contains \Drupal\quickscript\QuickScriptInterface.
 */

namespace Drupal\quickscript;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Quick Script entities.
 *
 * @ingroup quickscript
 */
interface QuickScriptInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Quick Script name.
   *
   * @return string
   *   Name of the Quick Script.
   */
  public function getName();

  /**
   * Sets the Quick Script name.
   *
   * @param string $name
   *   The Quick Script name.
   *
   * @return \Drupal\quickscript\QuickScriptInterface
   *   The called Quick Script entity.
   */
  public function setName($name);

  /**
   * Gets the Quick Script creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Quick Script.
   */
  public function getCreatedTime();

  /**
   * Sets the Quick Script creation timestamp.
   *
   * @param int $timestamp
   *   The Quick Script creation timestamp.
   *
   * @return \Drupal\quickscript\QuickScriptInterface
   *   The called Quick Script entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Quick Script published status indicator.
   *
   * Unpublished Quick Script are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Quick Script is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Quick Script.
   *
   * @param bool $published
   *   TRUE to set this Quick Script to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\quickscript\QuickScriptInterface
   *   The called Quick Script entity.
   */
  public function setPublished($published);

}
