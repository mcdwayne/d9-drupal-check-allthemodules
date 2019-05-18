<?php

namespace Drupal\oh_test;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\oh\Event\OhEvents;
use Drupal\oh\Event\OhRegularEvent;
use Drupal\oh\OhOccurrence;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for OH events.
 */
class OhTestRegularSubscriber implements EventSubscriberInterface {

  /**
   * Scenarios to run.
   */
  protected $scenarios = [];

  /**
   * Allow tests to set scenarios remotely.
   *
   * @param array $scenarios
   *   A list of scenarios.
   */
  public function setScenarios(array $scenarios): void {
    $this->scenarios = $scenarios;
  }

  /**
   * Test regular hours.
   *
   * @param \Drupal\oh\Event\OhRegularEvent $event
   *   Regular hours event.
   */
  public function regularHours(OhRegularEvent $event): void {
    if (in_array('every_day_2015', $this->scenarios)) {
      $endDay = new DrupalDateTime('1 January 2016 00:00');
      $dayPointer = new DrupalDateTime('1 January 2015 9am');
      while ($dayPointer < $endDay) {
        $start = clone $dayPointer;
        $end = (clone $start)->modify('+8 hours');

        $occurrence = (new OhOccurrence($start, $end))
          ->setIsOpen(TRUE);
        $event->addRegularHours($occurrence);

        $dayPointer->modify('+1 day');
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[OhEvents::REGULAR][] = ['regularHours'];
    return $events;
  }

}
