<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for entity-pilot queue workers.
 */
abstract class EntityPilotQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Air traffic control service.
   *
   * @var \Drupal\entity_pilot\AirTrafficControlInterface
   */
  protected $airTrafficControl;

  /**
   * Constructs a new queue worker.
   *
   * @param array $configuration
   *   Configuration of plugin.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\entity_pilot\AirTrafficControlInterface $air_traffic_control
   *   Air traffic control service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AirTrafficControlInterface $air_traffic_control) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->airTrafficControl = $air_traffic_control;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_pilot.air_traffic_control'));
  }

}
