<?php

namespace Drupal\feature_toggle;

use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\Core\State\StateInterface;
use Drupal\feature_toggle\Event\FeatureUpdateEvent;
use Drupal\feature_toggle\Event\FeatureUpdateEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FeatureStatus.
 */
class FeatureStatus implements FeatureStatusInterface {

  use FeatureUtilsTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new FeatureToggleStatus object.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher) {
    $this->state = $state;
    $this->config = $config_factory->getEditable('feature_toggle.features');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus($name) {
    $flags = $this->getStatusFlags();
    return !empty($flags[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(FeatureInterface $feature, $status) {
    $flags = $this->getStatusFlags();
    $flags[$feature->name()] = $status;
    $this->saveStatusFlags($flags);
    $this->dispatch($feature, $status);
  }

  /**
   * Dispatches the Feature Update events.
   *
   * @param FeatureInterface $feature
   *   The updated feature.
   * @param bool $status
   *   The new status.
   */
  protected function dispatch(FeatureInterface $feature, $status) {
    $event = new FeatureUpdateEvent($feature, $status);
    $this->eventDispatcher->dispatch(FeatureUpdateEvents::UPDATE, $event);
    $this->eventDispatcher->dispatch(FeatureUpdateEvents::UPDATE . '.' . $feature->name(), $event);
  }

}
