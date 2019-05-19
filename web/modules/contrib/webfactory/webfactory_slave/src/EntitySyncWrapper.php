<?php

namespace Drupal\webfactory_slave;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Wrap a remote entity and handle its state with local entity.
 *
 * @package Drupal\webfactory_slave
 */
class EntitySyncWrapper {

  /**
   * State new if no local entity exists.
   */
  const NEW_ENTITY = 'NEW';

  /**
   * State updated if there are no differences between remote & local entity.
   */
  const UPDATED = 'UPDATED';

  /**
   * State if there are new differences between remote & local entity.
   */
  const NEEDS_UPDATE = 'NEEDS_UPDATE';

  /**
   * State if the content was modified locally.
   */
  const MODIFIED_LOCALLY = 'MODIFIED_LOCALLY';

  /**
   * Same uuid shared by local & remote entity.
   *
   * @var string
   */
  protected $uuid = NULL;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType = NULL;

  /**
   * The local entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $localEntity = NULL;

  /**
   * The remote entity.
   *
   * @var array
   */
  protected $remoteEntity = NULL;

  /**
   * EntitySyncWrapper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type.
   * @param array $remote_entity
   *   Remote entity.
   * @param EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, $remote_entity, EntityRepositoryInterface $entity_repository) {
    $this->uuid = $remote_entity['uuid'][0]['value'];

    $this->entityType = $entity_type;
    $this->remoteEntity = $remote_entity;

    $entity = $entity_repository->loadEntityByUuid($entity_type->id(), $this->uuid);

    if ($entity) {
      $this->localEntity = $entity;
    }
  }

  /**
   * Check if remote entity is new.
   *
   * @return bool
   *   True if remote entity has no local copy, false otherwise.
   */
  public function isNew() {
    return $this->localEntity !== NULL;
  }

  /**
   * Retrieves local entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Local entity.
   */
  public function getLocalEntity() {
    return $this->localEntity;
  }

  /**
   * Return update status between remote and local entity.
   *
   * @return string
   *   Update status.
   */
  public function getStatus() {
    $status = self::NEW_ENTITY;

    if ($this->localEntity) {
      if ($this->localEntity->getChangedTime() < $this->remoteEntity['changed'][0]['value']) {
        $status = self::NEEDS_UPDATE;
      }
      elseif ($this->localEntity->getChangedTime() > $this->remoteEntity['changed'][0]['value']) {
        $status = self::MODIFIED_LOCALLY;
      }
      else {
        $status = self::UPDATED;
      }
    }
    return $status;
  }

  /**
   * Getter uuid.
   *
   * @return string
   *   Shared uuid.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Retrieve title of entity.
   *
   * If a local copy exists, title of local entity is used.
   *
   * @return string
   *   Entity title.
   */
  public function getTitle() {
    if ($this->localEntity) {
      $title = $this->localEntity->label();
    }
    elseif ($label = $this->entityType->getKey('label')) {
      $title = $this->remoteEntity[$label][0]['value'];
    }
    else {
      $title = '';
    }
    return $title;
  }

}
