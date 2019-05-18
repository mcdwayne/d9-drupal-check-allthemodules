<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar Event entities.
 *
 * @ingroup google_calendar
 */
interface GoogleCalendarEventInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Google Calendar Event name.
   *
   * @return string
   *   Name of the Google Calendar Event.
   */
  public function getName();

  /**
   * Sets the Google Calendar Event name.
   *
   * @param string $name
   *   The Google Calendar Event name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setName($name): GoogleCalendarEventInterface;


  /**
   * Gets the Google Calendar Event description.
   *
   * @return string
   *   Description of the Google Calendar Event.
   */
  public function getDescription();

  /**
   * Sets the Google Calendar Event description.
   *
   * @param string $description
   *   The Google Calendar Event description.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setDescription($description): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getCreatedTime();

  /**
   * Sets the Google Calendar Event creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar Event creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setCreatedTime($timestamp): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar event Start time.
   *
   * @return \DateTime
   *   Creation time of the Google Calendar Event.
   */
  public function getStartTime() : \DateTime;

  /**
   * Sets the Google Calendar event Start time.
   *
   * @param \DateTime $start
   *   The Google Calendar event Start time.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setStartTime(\DateTime $start): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar event End time.
   *
   * @return \DateTime
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getEndTime() : \DateTime;

  /**
   * Sets the Google Calendar Event creation timestamp.
   *
   * @param \DateTime $start
   *   The Google Calendar Event creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setEndTime(\DateTime $start): GoogleCalendarEventInterface;

  /**
   * Return whether the End Time of the event is meaningful.
   *
   * Reflects the Google Calendar "end_time_unspecified" flag.
   *
   * @return bool
   *   Creation timestamp of the Google Calendar Event.
   *
   * @see GoogleCalendarEventInterface::getEndTime()
   */
  public function isEndTimeSpecified() : bool;

  /**
   * Define whether the End time of the event is meaningful.
   *
   * Reflects the Google Calendar "end_time_unspecified" flag.
   *
   * @param bool $specified
   *   True if this event has a defined end time.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
//  public function setEndTimeSpecified(bool $specified): GoogleCalendarEventInterface;

  /**
   * Can guests of the event modify the event itself -- or just the owner?.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function canGuestsModifyEvent(): bool;

  /**
   * Can guests of the event see who else is invited -- or can only the owner do so?.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function canGuestsSeeInvitees(): bool;

  /**
   * Is the event Locked at the Google end?
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function isLocked(): bool;
  
  /**
   * Returns the Google Calendar Event published status indicator.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function isPublished(): bool;

  /**
   * Sets the published status of a Google Calendar Event.
   *
   * @param bool $published
   *   TRUE to set this Google Calendar Event to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setPublished($published);

  /**
   * Gets an iCal Identifier for the event.
   *
   * @return string
   *   iCal Identifier of the Google Calendar Event.
   */
  public function getGoogleICalId(): string;

  /**
   * Gets the Google Calendar event Start time.
   *
   * @return \Drupal\Core\Url
   *   URL linking to the event on the Google website.
   */
  public function getGoogleLink(): string;

  /**
   * Gets the Google Calendar ID of the event.
   *
   * @return string
   *   Calendar ID of the event.
   */
  public function getGoogleEventId(): string;

}
