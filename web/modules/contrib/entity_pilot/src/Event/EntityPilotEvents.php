<?php

namespace Drupal\entity_pilot\Event;

/**
 * Defines a class for holding entity pilot event constants.
 */
class EntityPilotEvents {

  /**
   * Name of the event fired when passengers are being prepared.
   *
   * This event allows modules to perform an action before passengers are
   * denormalized - allowing incoming passengers to be manipulated. The event
   * receives a \Drupal\entity_pilot\Event\PreparePassengersEvent instance.
   *
   * @Event
   *
   * @see \Drupal\entity_pilot\Event\PreparePassengersEvent
   * @see \Drupal\entity_pilot\Customs::screen()
   */
  const PREPARE_PASSENGERS = 'entity_pilot.prepare_passengers';

  /**
   * Name of the event fired when baggage handler is calculating dependencies.
   *
   * This event allows modules to perform an action when baggage is being
   * calculated - allowing additional baggage to be added. The event receives a
   * \Drupal\entity_pilot\Event\CalculateDependenciesEvent instance.
   *
   * @Event
   *
   * @see \Drupal\entity_pilot\Event\CalculateDependenciesEvent
   * @see \Drupal\entity_pilot\BaggageHandler::calculateDependencies
   */
  const CALCULATE_DEPENDENCIES = 'entity_pilot.calculate_dependencies';

}
