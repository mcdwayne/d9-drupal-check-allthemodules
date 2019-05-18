<?php

namespace Drupal\entity_grants\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_generic\Access\GenericAccessControlHandler;

/**
 * Controls access based on the grants.
 *
 * @see \Drupal\entity\UncacheableEntityPermissionProvider
 */
class GrantsAccessControlHandler extends GenericAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isAllowed() && !$account->hasPermission('bypass entity grant access')) {
      $result = $this->checkGrantsAccess($entity, $operation, $account);
    }

    // Ensure that access is evaluated again when the entity changes.
    return $result->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkGrantsAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);

    $grants = \Drupal::service('entity_grants.manager')->getGrants($entity, $operation, $account);
    if ($grants) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }

  }

}
