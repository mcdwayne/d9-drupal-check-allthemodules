<?php

namespace Drupal\oh_test;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\oh\Event\OhEvents;
use Drupal\oh\Event\OhExceptionEvent;
use Drupal\oh\OhOccurrence;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for OH events.
 */
class OhTestExceptionSubscriber implements EventSubscriberInterface {

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
   * Test exceptions.
   *
   * @param \Drupal\oh\Event\OhExceptionEvent $event
   *   Exception event.
   */
  public function exceptions(OhExceptionEvent $event): void {
    if (in_array('mondays_2015', $this->scenarios)) {
      $endDay = new DrupalDateTime('1 January 2016 00:00');
      $dayPointer = new DrupalDateTime('1 January 2015 00:00');
      while ($dayPointer < $endDay) {
        // Monday = '1';
        if ($dayPointer->format('w') == 1) {
          $start = clone $dayPointer;
          $end = (clone $start)->setTime(23, 59, 59);

          $occurrence = (new OhOccurrence($start, $end))
            ->setMessage('Mondays are closed')
            ->setIsOpen(FALSE);
          $event->addException($occurrence);
        }

        $dayPointer->modify('+1 day');
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[OhEvents::EXCEPTIONS][] = ['exceptions'];
    return $events;
  }

}
