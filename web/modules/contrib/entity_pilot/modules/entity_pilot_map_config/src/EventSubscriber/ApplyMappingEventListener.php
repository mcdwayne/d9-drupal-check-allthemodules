<?php

namespace Drupal\entity_pilot_map_config\EventSubscriber;

use Drupal\entity_pilot_map_config\MappingHandlerInterface;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Drupal\entity_pilot\Event\PreparePassengersEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event listener for apply mappings during prepare passengers event.
 */
class ApplyMappingEventListener implements EventSubscriberInterface {

  /**
   * Mapping handler service.
   *
   * @var \Drupal\entity_pilot_map_config\MappingHandlerInterface
   */
  protected $mappingHandler;

  /**
   * Constructs a new ApplyMappingEventListener object.
   *
   * @param \Drupal\entity_pilot_map_config\MappingHandlerInterface $mapping_handler
   *   Mapping handler service.
   */
  public function __construct(MappingHandlerInterface $mapping_handler) {
    $this->mappingHandler = $mapping_handler;
  }

  /**
   * Applies mappings.
   *
   * @param \Drupal\entity_pilot\Event\PreparePassengersEvent $event
   *   Prepare passengers event.
   */
  public function applyMapping(PreparePassengersEvent $event) {
    $passengers = $event->getPassengers();
    $arrival = $event->getArrival();
    if (($field_mapping = $arrival->mapping_fields->entity) && $bundle_mapping = $arrival->mapping_bundles->entity) {
      $passengers = $this->mappingHandler->applyMappingPair($passengers, $field_mapping, $bundle_mapping);
      $event->setPassengers($passengers);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityPilotEvents::PREPARE_PASSENGERS => 'applyMapping',
    ];
  }

}
