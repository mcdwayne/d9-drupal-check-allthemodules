<?php

namespace Drupal\field_collection_access;

use Drupal\field_collection\FieldCollectionItemAccessControlHandler as fcFieldCollectionItemAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Replace access handler for Field collection items to add access control.
 */
class FieldCollectionItemAccessControlHandler extends fcFieldCollectionItemAccessControlHandler {

  /**
   * The field collection grant storage.
   *
   * @var \Drupal\field_collection_access\FieldCollectionItemAccessStorage
   */
  protected $grantStorage;

  /**
   * Constructs a FieldCollectionItemAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->grantStorage = \Drupal::service('field_collection_access.grant_storage');
  }

  /**
   * Performs access checks.
   *
   * Uses permissions from host entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'update', 'create' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isForbidden()) {
      return $result;
    }

    // By default field collection access is dependant on host access.
    // Field collection route requirements require update priv on host to
    // delete field collection item.
    $host_operation = $operation;
    if ($host_operation == 'delete') {
      $host_operation = 'update';
    }
    $hostAccess = $entity->getHost()->access($host_operation, $account, TRUE);
    if ($hostAccess) {
      return $hostAccess->andIf($this->grantStorage->checkGrants($entity, $operation, $account));
    }
    return $hostAccess;
  }

}
