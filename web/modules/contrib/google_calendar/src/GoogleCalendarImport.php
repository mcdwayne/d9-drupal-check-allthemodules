<?php

namespace Drupal\google_calendar;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Exception;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;

use DateTime;
use DateTimeZone;

/**
 * Class YoutubeImport.
 *
 * @package Drupal\city_departments
 */
class GoogleCalendarImport {

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


  /**
   * Constructor.
   */
  public function __construct(Google_Client $googleClient, ConfigFactory $config, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->service = new Google_Service_Calendar($googleClient);

    $this->config = $config->getEditable('google_calendar.last_imports');

    $this->entityTypeManager = $entityTypeManager;

    $this->logger = $loggerChannelFactory->get('google_calendar');

  }

  public function import(GoogleCalendar $calendar, $ignoreSyncToken = FALSE){

    $calendarId = $calendar->getGoogleCalendarId();
    $configKey = "config_for_calendar_$calendarId";
    $syncToken = $ignoreSyncToken ? null : $this->config->get($configKey);

    $googleCalendar = $this->service->calendars->get($calendarId);

    // init dummy page token
    $nextPageToken = null;

    $pageCount = 0;
    do{
      $page = $this->getPage($calendarId, $syncToken, $nextPageToken);

      if(!$page){
        return FALSE;
      }

      $nextPageToken = $page->nextPageToken;
      $nextSyncToken = $page->nextSyncToken;
      $items = $page->getItems();
      if(count($items) > 0){
        $this->syncEvents($items, $calendar, $googleCalendar->getTimeZone());
      }
      $pageCount++;
    } while($nextPageToken && $pageCount < 10);

    //set sync token
    $this->config->set($configKey, $nextSyncToken);
    $this->config->save();

    $this->logger->info("Calendar: @calendar imported successfully.", [
      '@calendar' => $calendar->label()
    ]);

    return TRUE;
  }

  private function getPage($calendarId, $syncToken, $pageToken = null){
    try{
      $opts = [
        'pageToken' => $pageToken,
        'singleEvents' => TRUE,
        'fields' => 'nextPageToken, nextSyncToken, items(id,status,summary,description,location,start,end, extendedProperties)'
      ];

      if($syncToken){
        $opts['syncToken'] = $syncToken;
      }else{
        $opts['orderBy'] = 'startTime';
        $opts['timeMin'] = date(DateTime::RFC3339,strtotime("-1 day"));
      }

      $response = $this->service->events->listEvents($calendarId, $opts);
    }catch(Google_Service_Exception $e){
      // Catch token expired and re-pull
      if($e->getCode() == 410){
        $response = $this->getPage($calendarId, null);
      }else{
        $response = False;
      }
    }

    return $response;

  }

  private function syncEvents($events, $calendar, $timezone){

    // Get list of event Ids
    $eventIds = [];
    foreach ($events as $event){
      $eventIds[] = $event['id'];
    }

    // Query to get list of existing events
    $query = $this->entityTypeManager
              ->getStorage('google_calendar_event')
              ->getQuery('AND')
              ->condition('event_id', $eventIds, 'IN');

    $drupalEventIds = $query->execute();

    $drupalEvents = GoogleCalendarEvent::loadMultiple($drupalEventIds);

    // Index the existing event nodes by Google Calendar Id for easier lookup
    $indexedEvents = [];
    foreach ($drupalEvents as $event){
      $indexedEvents[$event->getGoogleEventId()] = $event;
    }

    // Iterate over events and update Drupal nodes accordingly
    foreach ($events as $event){
      // Get the event node
      $eventEntity = isset($indexedEvents[$event['id']]) ? $indexedEvents[$event['id']] : null;

      // Cutoff for deleted events
      if($event['status'] == 'cancelled'){
        if($eventEntity){
          // if event is cancelled and we have an associated event node, remove it
          $eventEntity->delete();
        }
        continue;
      }

      // Handle new or updated events
      $startDate = $event['start']['date'] ? new DateTime($event['start']['date'], new DateTimeZone($timezone)) : DateTime::createFromFormat(DateTime::ISO8601, $event['start']['dateTime']);
      $endDate = $event['end']['date'] ? new DateTime($event['end']['date'], new DateTimeZone($timezone)) : DateTime::createFromFormat(DateTime::ISO8601, $event['end']['dateTime']);

      // Config fields
      $fields = [

        'name' => $event['summary'],

        'event_id' => [
          'value' => $event['id']
        ],

        'calendar' => [
          'target_id' => $calendar->id()
        ],

        'description' => [
          'value' => $event['description'],
          'format' => 'basic_html'
        ],

        'location' => [
          'value' => $event['location']
        ],

        'start_date' => [
          'value' => $startDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp()
        ],
        'end_date' => [
          'value' => $endDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp()
        ]
      ];


      if(!$eventEntity){
        $eventEntity = GoogleCalendarEvent::create($fields);
      }else{
        // Update the existing node in place
        foreach($fields as $key => $value){
          $eventEntity->set($key, $value);
        }
      }

      // Save it!
      $eventEntity->save();
    }
  }

}
