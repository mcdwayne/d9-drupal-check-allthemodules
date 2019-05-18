<?php

namespace Drupal\google_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_calendar\GoogleCalendarImportEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_calendar\GoogleCalendarImport;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;

/**
 * Class GoogleCalendarImportEventsController.
 */
class GoogleCalendarImportEventsController extends ControllerBase {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEvents
   */
  protected $googleCalendarImport;

  /**
   * Constructs a new GoogleCalendarImportEventsController object.
   */
  public function __construct(GoogleCalendarImportEvents $google_calendar_import) {
    $this->googleCalendarImport = $google_calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import_events')
    );
  }

  /**
   * ImportCalendar.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function ImportCalendar(GoogleCalendarInterface $google_calendar) {

    $this->googleCalendarImport->import($google_calendar);

    drupal_set_message($this->t('Events for the <strong>@calendar</strong> Calendar have been imported successfully!', [
      '@calendar' => $google_calendar->getName()
    ]));

    return $this->redirect('entity.google_calendar.collection');
  }

}
