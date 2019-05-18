<?php

namespace Drupal\healthcheck_events_test\EventSubscriber;

use Drupal\healthcheck\Event\HealthcheckCriticalEvent;
use Drupal\healthcheck\Event\HealthcheckCronEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck\Event\HealthcheckRunEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\State\StateInterface;

class EventTestSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Log a Healthcheck run.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckRunEvent $event
   *   The event.
   */
  public function doRun(HealthcheckRunEvent $event) {
    $this->state->set('healthcheck_events_test.doRun', TRUE);
  }

  /**
   * Log each critical finding in a report.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckCriticalEvent $event
   *   The event.
   */
  public function doCritical(HealthcheckCriticalEvent $event) {
    $this->state->set('healthcheck_events_test.doCritical', TRUE);
  }

  /**
   * Log that cron processing has occurred.
   *
   * @param \Drupal\healthcheck\Event\HealthcheckCriticalEvent $event
   *   The event.
   */
  public function doCron(HealthcheckCronEvent $event) {
    $this->state->set('healthcheck_events_test.doCron', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      HealthcheckEvents::CHECK_CRITICAL => [
        'doCritical',
      ],
      HealthcheckEvents::CHECK_CRON => [
        'doCron',
      ],
      HealthcheckEvents::CHECK_RUN => [
        'doRun',
      ],
    ];
  }

}
