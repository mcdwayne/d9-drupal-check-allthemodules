<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Entity\EntityInterface;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LoadLocalEntityEvent.
 *
 * Matches remote entities with local entities.
 *
 * @package Drupal\acquia_contenthub\Event
 */
class LoadLocalEntityEvent extends Event {

  /**
   * The CDF object.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject
   */
  protected $cdf;

  /**
   * The dependency stack.
   *
   * @var \Drupal\depcalc\DependencyStack
   */
  protected $stack;

  /**
   * The local entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * LoadLocalEntityEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   The CDF object for which to attempt to load a local entity.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack from which to extract related entity data.
   */
  public function __construct(CDFObject $cdf, DependencyStack $stack) {
    $this->cdf = $cdf;
    $this->stack = $stack;
  }

  /**
   * Get the CDF Object.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   The CDF object.
   */
  public function getCdf(): CDFObject {
    return $this->cdf;
  }

  /**
   * Get the dependency stack.
   *
   * @return \Drupal\depcalc\DependencyStack
   *   The Dependency Stack.
   */
  public function getStack(): DependencyStack {
    return $this->stack;
  }

  /**
   * Gets the local entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Checks whether it has an entity.
   *
   * @return bool
   *   TRUE if it has an entity, FALSE otherwise.
   */
  public function hasEntity() {
    return (bool) $this->entity;
  }

  /**
   * Set the entity that was loaded and add it to the stack.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @throws \Exception
   */
  public function setEntity(EntityInterface $entity): void {
    $wrapper = new DependentEntityWrapper($entity);
    $wrapper->setRemoteUuid($this->getCdf()->getUuid());
    $this->stack->addDependency($wrapper);
    $this->entity = $entity;
  }

}
