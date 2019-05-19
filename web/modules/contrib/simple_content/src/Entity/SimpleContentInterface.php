<?php

namespace Drupal\simple_content\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Simple content entities.
 *
 * @ingroup simple_content
 */
interface SimpleContentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Simple content name.
   *
   * @return string
   *   Name of the Simple content.
   */
  public function getName();

  /**
   * Sets the Simple content name.
   *
   * @param string $name
   *   The Simple content name.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentInterface
   *   The called Simple content entity.
   */
  public function setName($name);

  /**
   * Gets the Simple content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Simple content.
   */
  public function getCreatedTime();

  /**
   * Sets the Simple content creation timestamp.
   *
   * @param int $timestamp
   *   The Simple content creation timestamp.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentInterface
   *   The called Simple content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Simple content published status indicator.
   *
   * Unpublished Simple content are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Simple content is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Simple content.
   *
   * @param bool $published
   *   TRUE to set this Simple content to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\simple_content\Entity\SimpleContentInterface
   *   The called Simple content entity.
   */
  public function setPublished($published);

}
