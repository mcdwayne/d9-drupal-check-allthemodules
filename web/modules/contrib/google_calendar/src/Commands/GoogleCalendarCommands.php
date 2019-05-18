<?php

namespace Drupal\google_calendar\Commands;

use Consolidation\OutputFormatters\StructuredData\UnstructuredListData;
use Drupal\google_calendar\GoogleClientFactory;
use Drush\Commands\DrushCommands;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a drush.services.yml
 * in root of your module like this module does.
 */
class GoogleCalendarCommands extends DrushCommands {

  protected $container;

  public function __construct($container) {
    parent::__construct();
    $this->container = $container;
  }

  /**
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * @return mixed
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * @return mixed
   */
  public function getContainer() {
    return $this->container;
  }


  /**
   * List all Google calendars visible for this account.
   *
   * @command gcal:listCalendars
   * @aliases gcal:lc, gcal-lc
   *
   * @option raw
   *   Return the data in the form supplied by Google.
   *
   * @field-labels
   *   id: ID
   *   name: Name
   *   primary: IsPrimary
   *   desc: Description
   *   locn: Location
   *   colour: Colour
   *
   * @return RowsOfFields
   *   Table of calendars.
   */
  public function calendarList(): RowsOfFields {
    /** @var GoogleClientFactory $client_factory */
    $client_factory = \Drupal::service('google_calendar.google_client.factory');
    /** @var \Google_Client $client */
    $client = $client_factory->get();

    $service = new \Google_Service_Calendar($client);
    $list = $service->calendarList->listCalendarList();

    $items = $list->getItems();
    $result = [];
    /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
    foreach ($items as $calendar) {
      $cal = [
        'id' => $calendar->getId(),
        'primary' => $calendar->getPrimary() ? 'Yes' : 'No',
        'name' => $calendar->getSummary(),
        'desc' => $calendar->getDescription(),
        'locn' => $calendar->getLocation(),
        'colour' => $calendar->getForegroundColor() . ' on ' . $calendar->getBackgroundColor(),
      ];
      $result[] = $cal;
    }
    return new RowsOfFields($result);
  }

  /**
   * List the events for a particular calendar.
   *
   * @param string $calendar_id
   *   The Google Calendar-ID of the calendar to show events for
   * @param string $event_id
   *   The event id of the event to display (optional)
   * @param array $options
   *
   * @field-labels
   *   id: ID
   *   name: Name
   *   desc: Description
   *   locn: Location
   *   start: Start Date
   *   end: End Date
   * @option raw
   *   Return the data in the form supplied by Google.
   * @option limit
   *   Restrict the output to this many events. If not specified, limit to 20.
   *
   * @command gcal:listEvents
   * @aliases gcal:lev,gcal-lev
   *
   * @return RowsOfFields|UnstructuredListData
   *   Table of events.
   *
   * @throws \Exception
   *   Exception if the Start or End dates are badly defined.
   */
  public function eventList($calendar_id, $event_id = NULL, $options = ['format' => 'table', 'limit' => 20, 'raw' => FALSE]) {
    /** @var GoogleClientFactory $client_factory */
    $client_factory = \Drupal::service('google_calendar.google_client.factory');
    $client = $client_factory->get();

    $service = new \Google_Service_Calendar($client);
    $optParams = array(
      'maxResults' => is_numeric($options['limit']) ?: 20,
      'orderBy' => 'startTime',
      'singleEvents' => TRUE,
      'timeMin' => date('c'),  // only future events
    );

    if ($event_id) {
      $event = $service->events->get($calendar_id, $event_id, []);
      if ($options['raw']) {
        return new UnstructuredListData($event);
      }
      $result[] = $this->formatEvent($event);
    }
    else {
      $list = $service->events->listEvents($calendar_id, $optParams);
      /** @var \Google_Service_Calendar_Events $items[] */
      $items = $list->getItems();
      print_r($items);
      if ($options['raw']) {
        return new UnstructuredListData($items);
      }
      $result = [];
      /** @var \Google_Service_Calendar_Event $event */
      foreach ($items as $event) {
        $result[] = $this->formatEvent($event);
      }
    }
    return new RowsOfFields($result);
  }

  protected function formatEvent(\Google_Service_Calendar_Event $event) {
    $startdate = $event->getStart();
    $start = $startdate->getDateTime() ?? $startdate->getDate();
    $start = new \DateTime($start);
    $enddate = $event->getEnd();
    $end = $startdate->getDateTime() ?? $enddate->getDate();
    $end = new \DateTime($end);

    $ev_id = $event->getId();
    $entities = \Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);

    $ev = [
      'id' => $ev_id,
      'name' => $event->getSummary(),
      'desc' => $event->getDescription(),
      'locn' => $event->getLocation(),
      'start' => $start->format('d-m-y h:m'),
      'end' => $end->format('d-m-y h:m'),
    ];
    return $ev;
  }

  /**
   * List the events for a particular calendar.
   *
   * @command gcal:importEvents
   * @aliases gcal:iev,gcal-iev
   *
   * @param string $calendar_id
   *   Google calendar ID for the calendar to update
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *
   * @throws \Exception
   *   Exception if the Start or End dates are badly defined.
   */
  public function import($calendar_id): PropertyList {
    $pl = [];
    /** @var \Drupal\google_calendar\GoogleCalendarImportEvents $importer */
    $importer = \Drupal::service('google_calendar.import_events');

    if ($calendar_id) {
      /* Import this calendar */
      $entities = \Drupal::entityTypeManager()
        ->getStorage('google_calendar')
        ->loadByProperties(['calendar_id' => $calendar_id]);
      $pl['found'] = count($entities);
    }
    else {
      /* Import for all active calendars */
      $entities = \Drupal::entityTypeManager()
        ->getStorage('google_calendar')
        ->loadByProperties(['status' => 1]);
      $pl['found'] = count($entities);
    }

    if (is_array($entities)) {
      foreach ($entities as $entity) {
        drush_print(t('Updating calendar entity @label(@cal)',
                      ['@cal' => $entity->id(), '@label' => $entity->getName()]));
        $result = $importer->import($entity);
        if ($result) {
          $pl['imported'] += $importer->getStatSavedEvents();
          $pl['updated'] += $importer->getStatModifyEvents();
        }
        else {
          drush_print(t('... Update failed.'));
          $pl['failed']++;
        }
      }
    }

    return new PropertyList($pl);
  }

  /**
   * Display stored secrets.
   *
   * @command gcal:secrets
   * @aliases gcal-getsecrets,gcal-secs
   * @usage drush google_calendar:getsecrets
   *   Show what the configured secrets files contain.
   *
   * @return PropertyList
   */
  public function secrets(): PropertyList {
    /** @var \Drupal\google_calendar\GoogleCalendarSecretsFileInterface $credentials */
    $credentials = \Drupal::service('google_calendar.secrets_file');
    $filepath = $credentials->getFilePath();

    if (is_readable($filepath)) {
      $json = file_get_contents($filepath);
      $jsondict = json_decode($json, TRUE, 4, JSON_INVALID_UTF8_IGNORE);
      $pl = [
        'managed_file' => $filepath,
        'service_secrets' => $jsondict,
      ];
      return new PropertyList($pl);
    }
    throw new \UnexpectedValueException('Could not read managed secrets file: ' . $filepath);
  }

}
