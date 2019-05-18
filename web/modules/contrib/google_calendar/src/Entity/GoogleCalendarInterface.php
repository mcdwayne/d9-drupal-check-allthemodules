<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar entities.
 *
 * @ingroup google_calendar
 */
interface GoogleCalendarInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  public const SYNC_RESULT_EVENTS_IMPORTED = "events imported";  // events were updated
  public const SYNC_RESULT_NO_CALENDAR = "no calendar";          // the API does not expose this calendar (any more)
  public const SYNC_RESULT_NO_CHANGES = "no changes";            // sync successful but no change seen
  public const SYNC_RESULT_NO_SYNC = "no sync";                  // sync not yet attempted
  public const SYNC_RESULT_NET_ERROR = "net error";              // some sort of network error
  public const SYNC_RESULT_AUTH_ERROR = "auth error";            // some sort of authentication error

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Google Calendar name.
   *
   * @return string
   *   Name of the Google Calendar.
   */
  public function getName();

  /**
   * Sets the Google Calendar name.
   *
   * @param string $name
   *   The Google Calendar name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setName($name);

  /**
   * Gets the Google Calendar Description.
   *
   * @return string
   *   Description of the Google Calendar.
   */
  public function getDescription();

  /**
   * Sets the Google Calendar Description.
   *
   * @param string $desc
   *   The Google Calendar Description.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setDescription(string $desc);

  /**
   * Gets the Google Calendar name.
   *
   * @return string
   *   Name of the Google Calendar.
   */
  public function getLocation();

  /**
   * Sets the Google Calendar Location.
   *
   * @param string $locn
   *   The Google Calendar Location.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLocation(string $locn);

  /**
   * Gets the result of the most recent sync with Google.
   *
   * @return string
   *   Result, one of the SYNC_RESULT_* constants.
   */
  public function getSyncResult();

  /**
   * Sets the Google Calendar Sync result.
   *
   * @param string $result
   *   The Google Calendar sync result: one of the SYNC_RESULT_* constants.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setSyncResult(string $result);

  /**
   * Gets the time that the last event was imported from Google.
   *
   * @return int
   *   LatestEvent timestamp for the Google Calendar.
   */
  public function getLatestEventTime();

  /**
   * Sets the time that the last event was imported from Google.
   *
   * @param int $timestamp
   *   The Google Calendar LatestEvent timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLatestEventTime($timestamp);

  /**
   * Gets the time that the last sync was tried - whether or not changes found.
   *
   * @return int
   *   Sync timestamp of the Google Calendar.
   */
  public function getLastSyncTime();

  /**
   * Sets the Google Calendar last sync timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar Sync timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLastSyncTime($timestamp);

  /**
   * Gets the Google Calendar creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar.
   */
  public function getCreatedTime();

  /**
   * Sets the Google Calendar creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Google Calendar published status indicator.
   *
   * Unpublished Google Calendar are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Google Calendar.
   *
   * @param bool $published
   *   TRUE to set this Google Calendar to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setPublished($published);

}
