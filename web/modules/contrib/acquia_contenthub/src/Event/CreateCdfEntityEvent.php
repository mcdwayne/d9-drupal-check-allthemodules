<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event dispatched to create new CDF objects.
 *
 * Multiple CDF Objects can exist for a single entity. These are different
 * representations of the data for different use cases. The default from
 * ContentHub is to serialize the entity data in a format that can be
 * reinstantiated on a separate Drupal install, but other representations could
 * exists. As an example, the acquia_lift_support module renders the entity in
 * various view modes and languages and send individual CDF objects per render.
 */
class CreateCdfEntityEvent extends Event {

  /**
   * The entity being serialized.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The array of entity dependencies.
   *
   * @var array
   */
  protected $dependencies;

  /**
   * The CDF Objects generated for this entity.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject[]
   */
  protected $cdfs = [];

  /**
   * CreateCdfEntityEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being serialized.
   * @param array $dependencies
   *   The dependencies array.
   */
  public function __construct(EntityInterface $entity, array $dependencies = []) {
    $this->entity = $entity;
    $this->dependencies = $dependencies;
  }

  /**
   * The entity to which the field belongs.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity which the field belongs.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * The array of dependencies for this entity.
   *
   * @return array
   *   The array of dependencies.
   */
  public function getDependencies() {
    return $this->dependencies;
  }

  /**
   * Adds a CDF object for this entity.
   *
   * Multiple CDF objects can be created for a single entity. Different
   * representations for different purposes are completely allowable and
   * expected. An example of this would be the Lift Support module creates CDF
   * documents that represent the HTML output of a rendered entity, but
   * ContentHub creates CDF documents for the entity structure itself. The only
   * caveat is that these CDF objects must have different UUIDs. The ContentHub
   * subscribers that generate CDF objects for entities are considered the
   * canonical handlers and use the Entity's own UUID. All other
   * representations must generate and track their UUIDs separately.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   The CDF object.
   */
  public function addCdf(CDFObject $cdf) {
    $this->cdfs[] = $cdf;
  }

  /**
   * Get a CDF object from the stack of CDFs the event holds.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   The CDF object.
   */
  public function getCdf($uuid) {
    foreach ($this->cdfs as $cdf) {
      if ($cdf->getUuid() == $uuid) {
        return $cdf;
      }
    }
  }

  /**
   * Get the entire list of CDF Objects generated for this entity.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject[]
   *   The CDF list.
   */
  public function getCdfList() {
    return $this->cdfs;
  }

}
