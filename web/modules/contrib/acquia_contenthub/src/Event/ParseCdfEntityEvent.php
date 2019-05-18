<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\depcalc\DependencyStack;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * The event fired during importing a CDF into a local entity.
 *
 * EventSubscribers to this event are responsible for processing the CDFObject
 * into a Drupal entity. If you need to create a CDF parser that acts on an
 * entity already acted upon by the core of ContentHub, you can set a higher
 * priority for your subscriber and stop propagation of subsequent subscribers,
 * or you can set your priority to be lower than the core subscribers and just
 * modify the entity created by ContentHub before it is save. The second option
 * is preferable in most circumstances. Look at how the core module handles
 * file entities for further insight on this approach.
 */
class ParseCdfEntityEvent extends Event {

  /**
   * The CDF Array to be parsed.
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
   * The resulting entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Whether the set entity is mutable.
   *
   * @var bool
   */
  protected $mutable = TRUE;

  /**
   * ParseCdfEntityEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   The CDF being parsed.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The pre-loaded or created entity for data storage.
   */
  public function __construct(CDFObject $cdf, DependencyStack $stack, EntityInterface $entity = NULL) {
    $this->cdf = $cdf;
    $this->stack = $stack;
    $this->entity = $entity;
  }

  /**
   * Returns the CDF Array.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   The CDF being parsed.
   */
  public function getCdf() {
    return $this->cdf;
  }

  /**
   * Whether the set entity is mutable.
   *
   * @return bool
   *   TRUE if mutable; FALSE otherwise.
   */
  public function isMutable() {
    return $this->mutable;
  }

  /**
   * Sets whether the entity is mutable or not.
   *
   * @param bool $mutable
   *   Set to TRUE if mutable; FALSE otherwise.
   */
  public function setMutable($mutable = TRUE) {
    $this->mutable = $mutable;
  }

  /**
   * Whether an entity has been set or not.
   *
   * @return bool
   *   TRUE if has entity; FALSE otherwise.
   */
  public function hasEntity() {
    return (bool) $this->entity;
  }

  /**
   * Obtains the parsed entity from the CDF.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity parsed from the CDF.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the parsed entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity parsed from the CDF.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the dependency stack.
   *
   * @return \Drupal\depcalc\DependencyStack
   *   The dependency stack.
   */
  public function getStack() {
    return $this->stack;
  }

}
