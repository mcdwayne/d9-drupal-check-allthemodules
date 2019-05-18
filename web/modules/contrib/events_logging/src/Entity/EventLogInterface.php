<?php

namespace Drupal\events_logging\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event log entities.
 *
 * @ingroup events_logging
 */
interface EventLogInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Event log name.
   *
   * @return string
   *   Name of the Event log.
   */
  public function getName();

  /**
   * Sets the Event log name.
   *
   * @param string $name
   *   The Event log name.
   *
   * @return \Drupal\events_logging\Entity\EventLogInterface
   *   The called Event log entity.
   */
  public function setName($name);

  /**
   * Gets the Event log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event log.
   */
  public function getCreatedTime();

  /**
   * Sets the Event log creation timestamp.
   *
   * @param int $timestamp
   *   The Event log creation timestamp.
   *
   * @return \Drupal\events_logging\Entity\EventLogInterface
   *   The called Event log entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event log published status indicator.
   *
   * Unpublished Event log are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event log is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event log.
   *
   * @param bool $published
   *   TRUE to set this Event log to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\events_logging\Entity\EventLogInterface
   *   The called Event log entity.
   */
  public function setPublished($published);

}
