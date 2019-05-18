<?php

namespace Drupal\content_synchronizer\Events;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Import event.
 */
class ImportEvent extends Event {

  const ON_ENTITY_IMPORTER = 'content_synchronizer.on_entity_importer';

  /**
   * The imported entity.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $entity;

  /**
   * The entity gid.
   *
   * @var string
   */
  protected $gid;

  /**
   * Return the entity.
   *
   * @return \Drupal\Core\Entity\Entity
   *   THe entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   THe entity.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Return the entity gid.
   *
   * @return string
   *   The gid.
   */
  public function getGid() {
    return $this->gid;
  }

  /**
   * Set the gid.
   *
   * @param string $gid
   *   THe gid.
   */
  public function setGid($gid) {
    $this->gid = $gid;
  }

}
