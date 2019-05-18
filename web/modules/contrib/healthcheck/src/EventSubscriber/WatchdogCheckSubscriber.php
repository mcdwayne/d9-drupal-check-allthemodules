<?php

namespace Drupal\healthcheck\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\healthcheck\Event\HealthcheckCriticalEvent;
use Drupal\healthcheck\Event\HealthcheckCronEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck\Event\HealthcheckRunEvent;
use Drupal\healthcheck\Finding\FindingStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A simple check subscriber to record when a check was run to the Drupal log.
 */
class WatchdogCheckSubscriber implements EventSubscriberInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new EmailCheckSubscriber object.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->loggerFactory = $loggerFactory;
    $this->logger = $this->loggerFactory->get('healthcheck');
  }

  /**
   * Log a Healthcheck run.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckRunEvent $event
   *   The event.
   */
  public function doRun(HealthcheckRunEvent $event) {
    /** @var \Drupal\healthcheck\Report\ReportInterface $report */
    $report = $event->getReport();

    $this->logger->info('A report was run.', $report->toArray());
  }

  /**
   * Log each critical finding in a report.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckCriticalEvent $event
   *   The event.
   */
  public function doCritical(HealthcheckCriticalEvent $event) {
    /** @var \Drupal\healthcheck\Report\ReportInterface $report */
    $report = $event->getReport();

    // Get the findings by status.
    $findings = $report->getFindingsByStatus();

    // If there are critical findings (which there should be)...
    if (isset($findings[FindingStatus::CRITICAL])) {

      /** @var \Drupal\healthcheck\Finding\FindingInterface $critical */
      foreach ($findings[FindingStatus::CRITICAL] as $critical) {

        // Log each critical finding.
        $this->logger->critical('Critical finding @key: @label', [
          '@key' => $critical->getKey(),
          '@label' => $critical->getLabel(),
        ]);
      }
    }
  }

  /**
   * Log that cron processing has occurred.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckCriticalEvent $event
   *   The event.
   */
  public function doCron(HealthcheckCronEvent $event) {
    /** @var \Drupal\healthcheck\Report\ReportInterface $report */
    $report = $event->getReport();

    $this->logger->info('Healthcheck performed background tasks.', $report->toArray());
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      HealthcheckEvents::CHECK_RUN => [
        'doRun',
      ],
      HealthcheckEvents::CHECK_CRITICAL => [
        'doCritical',
      ],
      HealthcheckEvents::CHECK_CRON => [
        'doCron',
      ],
    ];
  }
}
