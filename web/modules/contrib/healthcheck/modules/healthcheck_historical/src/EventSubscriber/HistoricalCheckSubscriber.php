<?php

namespace Drupal\healthcheck_historical\EventSubscriber;

use Drupal\healthcheck\Event\HealthcheckCronEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck_historical\HistoricalServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Records reports that were performed in the background to the database.
 *
 * By recording only background reports, we get consistent processing while
 * avoiding ad hoc reports from hammering the database.
 */
class HistoricalCheckSubscriber implements EventSubscriberInterface {

  /**
   * Healthcheck Historical service.
   *
   * @var \Drupal\healthcheck_historical\HistoricalServiceInterface
   */
  protected $historicalService;

  /**
   * Constructor.
   */
  public function __construct(HistoricalServiceInterface $historicalService) {
    $this->historicalService = $historicalService;
  }

  /**
   * Log reports performed in the background.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckRunEvent $event
   *   The event.
   */
  public function doCron(HealthcheckCronEvent $event) {
    /** @var \Drupal\healthcheck\Report\ReportInterface $report */
    $report = $event->getReport();

    $this->historicalService->saveReport($report);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      HealthcheckEvents::CHECK_CRON => [
        'doCron',
      ],
    ];
  }
}
