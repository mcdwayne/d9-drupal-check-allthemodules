<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event for invoking post-entity-save operations.
 *
 * This event has no setters because it is purely informational. It informs
 * subscribers of an event and invites them to perform related actions but not
 * to modify any variables relevant to the event itself.
 */
class EntityImportEvent extends Event {

  /**
   * The entity that was imported.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The source array data that was used to create the entity.
   *
   * This is useful for situations where you might want to refer to the data
   * as it existed before an entity was created.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject
   */
  protected $entityData;

  /**
   * EntityImportEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity tht was saved.
   * @param \Acquia\ContentHubClient\CDF\CDFObject $entity_data
   *   The CDF level data from which we saved the entity.
   */
  public function __construct(EntityInterface $entity, CDFObject $entity_data) {
    $this->entity = $entity;
    $this->entityData = $entity_data;
  }

  /**
   * Get the entity that was imported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get the entity data that was used to create the entity.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   The CDF object.
   */
  public function getEntityData() {
    return $this->entityData;
  }

}
