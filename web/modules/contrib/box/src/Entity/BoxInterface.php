<?php

namespace Drupal\box\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Box entities.
 *
 * @ingroup box
 */
interface BoxInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the box is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the box is published.
   */
  const PUBLISHED = 1;

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the bundle label.
   *
   * @return string|false
   *   The box bundle label or FALSE if the box bundle is not found.
   */
  public function bundleLabel();

  /**
   * Gets the Box title.
   *
   * @return string
   *   Title of the Box.
   */
  public function getTitle();

  /**
   * Sets the Box title.
   *
   * @param string $title
   *   The Box name.
   *
   * @return \Drupal\box\Entity\BoxInterface
   *   The called Box entity.
   */
  public function setTitle($title);

  /**
   * Gets the Box creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Box.
   */
  public function getCreatedTime();

  /**
   * Sets the Box creation timestamp.
   *
   * @param int $timestamp
   *   The Box creation timestamp.
   *
   * @return \Drupal\box\Entity\BoxInterface
   *   The called Box entity.
   */
  public function setCreatedTime($timestamp);

}
