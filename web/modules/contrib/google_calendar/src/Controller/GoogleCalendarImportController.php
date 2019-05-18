<?php

namespace Drupal\google_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_calendar\GoogleCalendarImport;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;

/**
 * Class GoogleCalendarImportController.
 */
class GoogleCalendarImportController extends ControllerBase {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImport
   */
  protected $googleCalendarImport;

  /**
   * Constructs a new GoogleCalendarImportController object.
   */
  public function __construct(GoogleCalendarImport $google_calendar_import) {
    $this->googleCalendarImport = $google_calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import')
    );
  }

  /**
   * Importcalendar.
   *
   * @return string
   *   Return Hello string.
   */
  public function ImportCalendar(GoogleCalendarInterface $google_calendar) {

    $this->googleCalendarImport->import($google_calendar);

    drupal_set_message($this->t('Events for the <strong>@calendar</strong> Calendar have been imported successfully!', [
      '@calendar' => $google_calendar->label()
    ]));

    return $this->redirect('entity.google_calendar.collection');
  }

}
