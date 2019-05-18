<?php

namespace Drupal\google_calendar;

use DateTime;
use DateTimeZone;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Google_Client;
use Google_Service_Calendar;
use Drupal\user\Entity\User;
use Google_Service_Exception;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;

/**
 * Class GoogleCalendarImportCalendar.
 */
class GoogleCalendarImportCalendar {

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

  /**
   * @var
   */
  protected $config;

  /**
   * EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
    $this->config = $config->get('google_calendar');
  }

  public function import(string $calendarId) {
    $entities = [];
    $calEntity = NULL;

    // Do we already have this calendar? Try using either entity id or google calendar-id
    if (is_numeric($calendarId)) {
      $entities = $this->entityTypeManager
        ->getStorage('google_calendar')
        ->load($calendarId);

      if ($entities !== NULL) {
        $calEntity = $entities;
        $calendarId = $calEntity->getGoogleCalendarId();
      }
    }
    if (!$entities) {
      $entities = $this->entityTypeManager
        ->getStorage('google_calendar')
        ->loadByProperties(['calendar_id' => $calendarId]);

      if (count($entities)) {
        $calEntity = reset($entities);
        $calendarId = $calEntity->getGoogleCalendarId();
      }
    }

    try {
      $calendar = $this->service->calendars->get($calendarId);
    }
    catch (Google_Service_Exception $e) {
      return FALSE;
    }

    $fields = [
      'calendar_id' => ['value' => $calendarId],
      'name' => ['value' => $calendar->getSummary()],
      'description' => ['value' => $calendar->getDescription()],
      'location' => ['value' => $calendar->getLocation()],
    ];

    if (!$calEntity) {
      $fields['status'] = ['value' => TRUE];
      $fields['sync_result'] = ['value' => GoogleCalendar::SYNC_RESULT_NO_SYNC];
      $fields['last_checked'] = ['value' => time()];
      $fields['latest_event'] = ['value' => time()];

      $calEntity = GoogleCalendar::create($fields);
    }
    else {
      $fields['last_checked'] = ['value' => time()];

      // Update the existing node in place
      foreach ($fields as $key => $value) {
        $calEntity->set($key, $value);
      }
    }

    if ($calEntity) {
      $calEntity->save();
    }
    return $calEntity;
  }

}
