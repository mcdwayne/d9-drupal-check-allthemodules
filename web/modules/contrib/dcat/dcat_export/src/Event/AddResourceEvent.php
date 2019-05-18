<?php

namespace Drupal\dcat_export\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\Event;
use EasyRdf_Resource;

/**
 * Provides an add-resource event for event listeners.
 */
class AddResourceEvent extends Event {

  /**
   * EasyRdf resource object.
   *
   * @var \EasyRdf_Resource
   */
  protected $resource;

  /**
   * Entity object.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Constructs an add-resource event object.
   *
   * @param \EasyRdf_Resource $resource
   *   The EasyRdf resource, based on the given entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is processed into the resource.
   */
  public function __construct(EasyRdf_Resource $resource, ContentEntityInterface $entity) {
    $this->resource = $resource;
    $this->entity = $entity;
  }

  /**
   * Gets the resource object.
   *
   * Alter this object in order to alter the DCAT export feed. Note that the
   * object is a reference. There is no need to set the object after altering.
   *
   * @return \EasyRdf_Resource
   *   The EasyRdf resource, based on the entity in this event object.
   */
  public function getResource() {
    return $this->resource;
  }

  /**
   * Gets the entity which has been processed into the RDF resource.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The EasyRdf resource, based on the entity in this event object.
   */
  public function getEntity() {
    return $this->entity;
  }

}
