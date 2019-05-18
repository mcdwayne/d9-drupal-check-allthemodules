<?php

namespace Drupal\dea_request\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class AccessRequestAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'accept' && $entity->getStatus() == AccessRequest::ACCEPTED) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    if ($operation == 'deny' && $entity->getStatus() == AccessRequest::DENIED) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}