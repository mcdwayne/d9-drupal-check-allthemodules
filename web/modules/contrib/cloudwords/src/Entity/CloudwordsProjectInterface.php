<?php

namespace Drupal\cloudwords\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cloudwords project entities.
 *
 * @ingroup cloudwords
 */
interface CloudwordsProjectInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // @todo Add get/set methods for your configuration properties here.
  /**
   * Gets the Cloudwords project name.
   *
   * @return string
   *   Name of the Cloudwords project.
   */
  public function getName();

  /**
   * Sets the Cloudwords project name.
   *
   * @param string $name
   *   The Cloudwords project name.
   *
   * @return \Drupal\cloudwords\Entity\CloudwordsProjectInterface
   *   The called Cloudwords project entity.
   */
  public function setName($name);

  /**
   * Gets the Cloudwords project creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cloudwords project.
   */
  public function getCreatedTime();

  /**
   * Sets the Cloudwords project creation timestamp.
   *
   * @param int $timestamp
   *   The Cloudwords project creation timestamp.
   *
   * @return \Drupal\cloudwords\Entity\CloudwordsProjectInterface
   *   The called Cloudwords project entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cloudwords project published status indicator.
   *
   * Unpublished Cloudwords project are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cloudwords project is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cloudwords project.
   *
   * @param bool $published
   *   TRUE to set this Cloudwords project to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\cloudwords\Entity\CloudwordsProjectInterface
   *   The called Cloudwords project entity.
   */
  public function setPublished($published);

}
