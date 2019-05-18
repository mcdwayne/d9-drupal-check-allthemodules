<?php
/**
 * Created by PhpStorm.
 * User: dtrafton
 * Date: 1/9/18
 * Time: 11:18 AM
 */

namespace Drupal\google_calendar\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_calendar\GoogleCalendarImport;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 *
 * @QueueWorker(
 *   id = "google_calendar_import_processor",
 *   title = "Google Calendar Import Processor",
 *   cron = {"time" = 60}
 * )
 */
class CalendarImportProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImport
   */
  protected $calendarImport;

  /**
   * constructor
   */
  public function __construct(GoogleCalendarImport $calendar_import) {
    $this->calendarImport = $calendar_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('google_calendar.import_events')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($calendar) {
    $this->calendarImport->import($calendar);
  }



}