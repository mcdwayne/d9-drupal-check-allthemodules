<?php

namespace Drupal\stats\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Stat processor entities.
 */
interface StatProcessorInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getTriggerEntityType(): string;

  /**
   * @param string $triggerEntityType
   *
   * @return StatProcessorInterface
   */
  public function setTriggerEntityType(string $triggerEntityType): StatProcessorInterface;

  /**
   * @return string
   */
  public function getTriggerBundle(): string;

  /**
   * @param string $triggerBundle
   *
   * @return StatProcessor
   */
  public function setTriggerBundle(string $triggerBundle): StatProcessorInterface;

  /**
   * @return array
   */
  public function getDependencies(): array;

  /**
   * @param array $dependencies
   *
   * @return StatProcessor
   */
  public function setDependencies(array $dependencies): StatProcessorInterface;

  /**
   * @return array
   */
  public function getTags(): array;

  /**
   * @param array $tags
   *
   * @return StatProcessor
   */
  public function setTags(array $tags): StatProcessorInterface;

  /**
   * Checks if the given Entity is supported.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return bool
   */
  public function supportsTriggerEntity(ContentEntityInterface $entity);

  /**
   * Retrieves the plugin id of the source.
   *
   * @return string
   */
  public function getSourcePluginID();

  /**
   * Get source configuration.
   *
   * @return array
   */
  public function getSource(): array;

  /**
   * Set source configuration.
   *
   * @param array $source
   */
  public function setSource(array $source): StatProcessorInterface;

  /**
   * Retrieves the plugin id of the destination.
   *
   * @return string
   */
  public function getDestinationPluginID();

  /**
   * Get destination configuration.
   *
   * @return array
   */
  public function getDestination(): array;

  /**
   * Set destination configuration.
   *
   * @param array $destination
   */
  public function setDestination(array $destination): StatProcessorInterface;

  /**
   * Get steps configuration.
   *
   * @return array
   */
  public function getSteps(): array;

  /**
   * Set process configuration.
   *
   * @param array $process
   */
  public function setSteps(array $steps): StatProcessorInterface;


  /**
   * Get the weight of given processor.
   *
   * @return int
   */
  public function getWeight(): int;

  /**
   * Sets weight of processor.
   *
   * @param int $weight
   *
   * @return StatProcessor
   */
  public function setWeight(int $weight): StatProcessorInterface;

}
