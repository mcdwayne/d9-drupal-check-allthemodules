<?php

namespace Drupal\log_entity\EventSubscriber;

use Drupal\log_entity\Event\LogEvent;
use Drupal\log_entity\EventLoggerInterface;
use Drupal\log_entity\LogEntityEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The log event subscriber.
 */
class LogEventSubscriber implements EventSubscriberInterface {

  /**
   * The event logger.
   *
   * @var \Drupal\log_entity\EventLoggerInterface
   */
  protected $eventLogger;

  /**
   * LogEventSubscriber constructor.
   *
   * @param \Drupal\log_entity\EventLoggerInterface $eventLogger
   *   The event logger for saving events.
   */
  public function __construct(EventLoggerInterface $eventLogger) {
    $this->eventLogger = $eventLogger;
  }

  /**
   * Triggered when an log event is dispatched.
   *
   * @param \Drupal\log_entity\Event\LogEvent $event
   *   Any kind of event that should be logged.
   */
  public function onEventCreated(LogEvent $event) {
    $this->eventLogger->logEvent($event);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LogEntityEvents::LOG_EVENT][] = ['onEventCreated'];
    return $events;
  }

}
