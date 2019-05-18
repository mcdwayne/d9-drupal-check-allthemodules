<?php

namespace Drupal\iots_measure\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Iots Measure entities.
 *
 * @ingroup iots_measure
 */
interface IotsMeasureInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Iots Measure name.
   *
   * @return string
   *   Name of the Iots Measure.
   */
  public function getName();

  /**
   * Sets the Iots Measure name.
   *
   * @param string $name
   *   The Iots Measure name.
   *
   * @return \Drupal\iots_measure\Entity\IotsMeasureInterface
   *   The called Iots Measure entity.
   */
  public function setName($name);

  /**
   * Gets the Iots Measure creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Iots Measure.
   */
  public function getCreatedTime();

  /**
   * Sets the Iots Measure creation timestamp.
   *
   * @param int $timestamp
   *   The Iots Measure creation timestamp.
   *
   * @return \Drupal\iots_measure\Entity\IotsMeasureInterface
   *   The called Iots Measure entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Iots Measure published status indicator.
   *
   * Unpublished Iots Measure are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Iots Measure is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Iots Measure.
   *
   * @param bool $published
   *   TRUE to set this Iots Measure to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\iots_measure\Entity\IotsMeasureInterface
   *   The called Iots Measure entity.
   */
  public function setPublished($published);

}
