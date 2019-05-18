<?php

namespace Drupal\google_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_calendar\GoogleCalendarImportCalendar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_calendar\GoogleCalendarImport;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;

/**
 * Class GoogleCalendarImportCalendarController.
 */
class GoogleCalendarImportCalendarController extends ControllerBase {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportCalendar
   */
  protected $calendarImport;

  /**
   *
   * @param GoogleCalendarImportCalendar $google_calendar_import
   *   Importer for Calendars.
   * Constructs a new GoogleCalendarImportEventsController object.
   */
  public function __construct(GoogleCalendarImportCalendar $google_calendar_import) {
    $this->calendarImport = $google_calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import_calendar')
    );
  }

  /**
   * ImportCalendar.
   *
   * @param string $calendar_id
   *   Google ID of the calendar to import.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function ImportCalendar(string $calendar_id) {

    $google_calendar = $this->calendarImport->import($calendar_id);

    if ($google_calendar) {
      drupal_set_message($this->t('The <strong>@calendar</strong> Calendar has been imported successfully!', [
        '@calendar' => $google_calendar->getName()
      ]));
    }
    else {
      drupal_set_message($this->t('The Calendar has not been imported successfully!'));
    }
    return $this->redirect('entity.google_calendar.collection');
  }

}
