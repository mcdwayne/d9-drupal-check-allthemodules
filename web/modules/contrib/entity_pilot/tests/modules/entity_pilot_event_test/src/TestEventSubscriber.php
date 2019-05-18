<?php

namespace Drupal\entity_pilot_event_test;

use Drupal\Core\State\StateInterface;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Drupal\entity_pilot\Event\PreparePassengersEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a class for a test event subscriber for entity pilot events.
 */
class TestEventSubscriber implements EventSubscriberInterface {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new TestEventSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Prepares passengers.
   *
   * @param \Drupal\entity_pilot\Event\PreparePassengersEvent $event
   *   Event metadata.
   */
  public function preparePassengers(PreparePassengersEvent $event) {
    $this->state->set('entity_pilot_test_event.result', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityPilotEvents::PREPARE_PASSENGERS => 'preparePassengers',
    ];
  }

}
