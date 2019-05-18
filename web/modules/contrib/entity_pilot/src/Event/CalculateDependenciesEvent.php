<?php

namespace Drupal\entity_pilot\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines a class for handling a calculate dependencies event.
 */
class CalculateDependenciesEvent extends Event {

  /**
   * Dependent entities keyed by UUID.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $dependencies = [];

  /**
   * Entity for which the dependencies are being calculated.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Cache tags.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * Constructs a new CalculateDependenciesEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the dependencies are being calculated.
   * @param \Drupal\Core\Entity\EntityInterface[] $dependencies
   *   Calculated dependencies.
   * @param array $tags
   *   Cache tags.
   */
  public function __construct(EntityInterface $entity, array $dependencies = [], array $tags = []) {
    $this->entity = $entity;
    $this->dependencies = $dependencies;
  }

  /**
   * Gets value of dependencies.
   *
   * @return array
   *   Value of dependencies
   */
  public function getDependencies() {
    return $this->dependencies;
  }

  /**
   * Sets dependencies.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $dependencies
   *   New value for calculated dependencies.
   *
   * @return CalculateDependenciesEvent
   *   Instance called.
   */
  public function setDependencies(array $dependencies) {
    $this->dependencies = $dependencies;
    return $this;
  }

  /**
   * Gets value of entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Value of entity
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets value of tags.
   *
   * @return array
   *   Value of tags
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * Sets value of tags.
   *
   * @param array $tags
   *   New tags.
   *
   * @return CalculateDependenciesEvent
   *   Instance called.
   */
  public function setTags(array $tags) {
    $this->tags = $tags;
    return $this;
  }

}
