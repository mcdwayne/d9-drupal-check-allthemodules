<?php

namespace Drupal\google_calendar;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\user\Entity\User;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Exception;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;

use DateTime;
use DateTimeZone;

/**
 * Class GoogleCalendarImportEvents.
 */
class GoogleCalendarImportEvents {

  // Handle dates: Google supplies values such as:
  //   "2010-01-09T16:06:35.311Z"
  // which is almost but not quite RFC3339/ISO8601: 3 digit fractional
  // seconds is neither RFC3339_EXTENDED nor RFC3339 compatible,
  // though it is a perfectly valid date representation.
  //
  //  ISO8601           'Y-m-d\TH:i:sO'  // no : in tz, secs
  //  RFC3339           'Y-m-d\TH:i:sP'  // : in tz, secs
  //  RFC3339_EXTENDED  'Y-m-d\TH:i:s.vP' // milliseconds
  //
  // Google API:
  //  created timestamp:
  //            "created": "2010-01-09T16:09:16.000Z",
  //  updated timestamp:
  //            "updated": "2019-03-29T20:01:48.229Z",
  //  Start date:
  //            "date": null,
  //            "dateTime": "2019-04-11T10:45:00+01:00",
  //            "timeZone": "Europe/London"
  //  End date:
  //            "date": null,
  //            "dateTime": "2019-04-11T12:00:00+01:00",
  //            "timeZone": "Europe/London"


  // Format of dates coming From API:

  // CRUD is used for created, updated timestamps, and has milliseconds in it.
  protected const DATESTYLE_CRUD = "Y-m-d\TH:i:s.uP";
  // WHEN is used for start, end times and has second (at most) granularity.
  protected const DATESTYLE_WHEN = "Y-m-d\TH:i:sO";

  /**
   * @var int stats
   */
  protected $modify_events = 0;
  protected $saved_events = 0;
  protected $created_events = 0;
  protected $page_count = 0;
  protected $new_events = 0;

  /**
   * @var int Maximum pages to import at once.
   */
  protected $maxPages = 2;

  /**
   * Google Calendar service definition.
   *
   * @var \Google_Service_Calendar
   */
  protected $service;


  /**
   * Logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  protected $config;

  /**
   * EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function resetStats() {
    $this->new_events = 0;
    $this->modify_events = 0;
    $this->saved_events = 0;
    $this->created_events = 0;
  }

  public function getStatNewEvents() {
    return $this->new_events;
  }

  public function getStatModifyEvents() {
    return $this->modify_events;
  }

  public function getStatCreatedEvents() {
    return $this->created_events;
  }

  public function getStatSavedEvents() {
    return $this->saved_events;
  }

  public function getPageCount() {
    return $this->page_count;
  }

  /**
   * GoogleCalendarImport constructor.
   *
   * @param \Google_Client $googleClient
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(Google_Client $googleClient, ConfigFactory $config,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {

    $this->service = new Google_Service_Calendar($googleClient);
    $this->config = $config->getEditable('google_calendar.last_imports');
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
  }

  public function import(GoogleCalendar $calendar, $ignoreSyncToken = FALSE) {

    $calendarId = $calendar->getGoogleCalendarId();
    $configKey = "config_for_calendar_$calendarId";
    $syncToken = $ignoreSyncToken ? NULL : $this->config->get($configKey);

    $googleCalendar = $this->service->calendars->get($calendarId);

    // init dummy page token
    $nextPageToken = NULL;

    // Stats
    $this->resetStats();

    // Page count limit.
    $this->pageCount = 0;

    $calendar->preImport($calendarId, $this);

    do {
      $page = $this->getPage($calendarId, $syncToken, $nextPageToken);

      if (!$page) {
        return FALSE;
      }

      $nextPageToken = $page->nextPageToken;
      $nextSyncToken = $page->nextSyncToken;
      $items = $page->getItems();
      if (count($items) > 0) {
        $this->syncEvents($items, $calendar, $googleCalendar->getTimeZone());
        $this->pageCount++;
      }
    } while ($nextPageToken && $this->pageCount < $this->maxPages);

    //set sync token
    $this->config->set($configKey, $nextSyncToken);

    $calendar->postImport($calendarId, $this);
    $this->config->save();

    $this->logger->info("Calendar: @calendar imported successfully.", [
      '@calendar' => $calendar->getName()
    ]);

    return $calendar;
  }

  /**
   * Request a page of calendar events for a calendar-id
   *
   * @param string $calendarId
   *   Calendar identifier.
   * @param string $syncToken
   *   Token obtained from the nextSyncToken field returned on the last page of
   *   results from the previous list request.
   * @param string $pageToken
   *   Token specifying which result page to return. Optional.
   *
   * @return bool|\Google_Service_Calendar_Events
   *
   * @throws Google_Service_Exception
   * @see https://developers.google.com/calendar/v3/reference/events/list
   */
  private function getPage($calendarId, $syncToken, $pageToken = NULL) {

    // also 'showDeleted', 'q', 'timeMax', 'timeZone', 'updatedMin', 'maxResults'.
    // default maxResults is 250 per page.

    $opts = [
      'pageToken' => $pageToken,
      'singleEvents' => TRUE,  // expand recurring events into instances.
    ];

    if ($syncToken) {
      $opts['syncToken'] = $syncToken;
    }
    else {
      $opts['orderBy'] = 'startTime';  // or 'updated'; 'startTime' requires 'singleEvents'=true
      $opts['timeMin'] = date(DateTime::RFC3339, strtotime("-1 day"));
    }

    try {
      $response = $this->service->events->listEvents($calendarId, $opts);
    }
    catch (Google_Service_Exception $e) {
      // Catch token expired and re-pull
      if ($e->getCode() == 410) {
        $response = $this->getPage($calendarId, NULL);
      }
      else {
        throw $e;
      }
    }

    return $response;
  }

  /**
   * Given a list of events, add or update the corresponding Calendar Entities.
   *
   * @param \Google_Service_Calendar_Event[] $events
   * @param GoogleCalendar $calendar
   * @param string $timezone
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function syncEvents($events, $calendar, $timezone) {

    // Get list of event Ids
    $eventIds = [];
    foreach ($events as $event) {
      $eventIds[] = $event['id'];
    }
    $this->new_events += count($eventIds);

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager
      ->getStorage('google_calendar_event');

    // Query to get list of existing events
    $query = $storage
      ->getQuery()
      ->condition('event_id', $eventIds, 'IN');

    $drupalEventIds = $query->execute();

    /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface[] $drupalEvents */
    $drupalEvents = GoogleCalendarEvent::loadMultiple($drupalEventIds);

    // Index the existing event nodes by Google Calendar Id for easier lookup.
    $indexedEvents = [];
    foreach ($drupalEvents as $event) {
      $indexedEvents[$event->getGoogleEventId()] = $event;
    }

    $this->modify_events += count($indexedEvents);

    // Iterate over incoming events and update Drupal entities accordingly.
    foreach ($events as $event) {

      // Get the old entity, if it exists.
      $eventEntity = $indexedEvents[$event['id']] ?? NULL;

      // If the API now states the event was cancelled, delete the entity.
      if ($event['status'] == 'cancelled') {
        if ($eventEntity) {
          // if event is cancelled and we have an associated event node, remove it
          $eventEntity->delete();
        }
        continue;
      }

      // Parse the CRUD event meta-dates.
      $createdDate = $this->parseCRUDDate($event->created);
      $updatedDate = $this->parseCRUDDate($event->updated);

      // Parse event start and end dates.
      $startDate = $this->parseAPIDate($timezone, $event->start);
      $endDate = $this->parseAPIDate($timezone, $event->end);

      // If possible, assign the drupal owner of this entity from the organiser email.
      $user_email = user_load_by_mail($event['organizer']->email);
      if ($user_email) {
        $user_id = $user_email->id();
      }
      else {
        $user_id = User::getAnonymousUser()->id();
      }

      // Config fields
      $fields = [
        'user_id' => ['target_id' => $user_id],
        'name' => $event['summary'],
        'event_id' => ['value' => $event['id']],
        'ical_id' => ['value' => $event['iCalUID']],
        'calendar' => ['target_id' => $calendar->id()],
        'start_date' => ['value' => $startDate],
        'end_date' => ['value' => $endDate],

        'google_link' => [
          'uri' => $event['htmlLink'],
          'title' => $event['summary'],
        ],

        'description' => [
          'value' => $event['description'],
          'format' => 'basic_html',
        ],

        'location' => ['value' => $event['location']],
        'locked' => ['value' => $event['locked'] ?? FALSE],
        'etag' => ['value' => $event['etag']],
        'transparency' => ['value' => $event['transparency']],
        'visibility' => ['value' => $event['visibility']],
        'guests_invite_others' => ['value' => $event['guestsCanInviteOthers']],
        'guests_modify' => ['value' => $event['guestsCanModify']],
        'guests_see_invitees' => ['value' => $event['guestsCanSeeOtherGuests']],
        'state' => ['value' => $event['status']],
        'organizer' => ['value' => $event['organizer']->displayName],
        'organizer_email' => ['value' => $event['organizer']->email],
        'creator' => ['value' => $event['creator']->displayName],
        'creator_email' => ['value' => $event['creator']->email],
        'created' => ['value' => $createdDate],
        'updated' => ['value' => $updatedDate],
      ];


      if (!$eventEntity) {
        $eventEntity = GoogleCalendarEvent::create($fields);
        $this->created_events++;
      }
      else {
        // Update the existing node in place
        foreach ($fields as $key => $value) {
          $eventEntity->set($key, $value);
        }
        $this->saved_events++;
      }

      // Save it!
      $eventEntity->save();
    }

    $this->logger->info('Sync "@calendar": @new_events fetched, @created_events created, @modify_events to update and @saved_events updated.',
                        [
                          '@calendar' => $calendar->getName(),
                          '@new_events' => $this->new_events,
                          '@modify_events' => $this->modify_events,
                          '@created_events' => $this->created_events,
                          '@saved_events' => $this->saved_events,
                        ]);
  }

  /**
   * Parse the user event dates.
   *
   * For start and end the 'date' value is set only when there is no time
   * component for the event, so check 'date' first, then if not set get
   * both date and time from 'dateTime'.
   * 
   * @param string $timezone
   *   A timezone specifier in a form suitable for \DateTimeZone().
   * @param \Google_Service_Calendar_EventDateTime $event
   * @return int
   *   Timestamp of the parsed date as Unix epoch seconds, UTC.
   *
   * @throws \InvalidArgumentException
   *   If the date cannot be converted.
   */
  private function parseAPIDate(string $timezone, \Google_Service_Calendar_EventDateTime $event) {
    try {
      if ($event['date']) {
        $theDate = new DateTime($event['date'], new DateTimeZone($timezone));
      }
      else {
        $theDate = DateTime::createFromFormat(self::DATESTYLE_WHEN, $event['dateTime']);
      }
      $theDate = $theDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
    }
    catch (\Exception $e) {
      throw new \InvalidArgumentException('Unable to parse date from event: ' . serialize($event), 0, $e);
    }
    return $theDate;
  }

  /**
   * Parse the CRUD event meta-dates.
   *
   * The check for 1970 is because sometimes small integers are seen here,
   * resulting in entity dates in 1970, which really messes things up later.
   *
   * @param string $event
   * @return int
   *   Timestamp of the parsed date as Unix epoch seconds, UTC.
   *
   * @throws \InvalidArgumentException
   *   If the date cannot be converted.
   */
  private function parseCRUDDate(string $event) {
    try {
      $createdDate = DateTime::createFromFormat(self::DATESTYLE_CRUD, $event);
      if (is_object($createdDate) && $createdDate->format('Y') > 1970) {
        $theDate = $createdDate->getTimestamp();
      }
      else {
        $theDate = 0;
      }
    }
    catch (\Exception $e) {
      throw new \InvalidArgumentException('Unable to parse date from event: ' . serialize($event), 0, $e);
    }

    return $theDate;
  }

}
