<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Event entities.
 *
 * @ingroup contacts_events
 */
interface EventInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event.
   */
  public function getCreatedTime();

  /**
   * Sets the Event creation timestamp.
   *
   * @param int $timestamp
   *   The Event creation timestamp.
   *
   * @return \Drupal\contacts_events\Entity\EventInterface
   *   The called Event entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event published status indicator.
   *
   * Unpublished Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event.
   *
   * @param bool $published
   *   TRUE to set this Event to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\contacts_events\Entity\EventInterface
   *   The called Event entity.
   */
  public function setPublished($published);

  /**
   * Check whether bookings are enabled for this event.
   *
   * @return bool
   *   Whether bookings are enabled.
   */
  public function isBookingEnabled();

  /**
   * Check whether bookings are open for this event.
   *
   * Additional permissions are required if bookings are closed.
   *
   * @return bool
   *   Whether bookings are open.
   */
  public function isBookingOpen();

}
