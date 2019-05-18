<?php

namespace Drupal\ptalk;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Defines the access control handler for the ptalk_thread entity type.
 *
 * @see \Drupal\ptalk\Entity\Thread
 */
class ThreadAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {

      case 'view':
        $access_result = AccessResult::allowedIf(($account->hasPermission('read private conversation') && $entity->participantOf($account)))->cachePerPermissions()->addCacheableDependency($entity);
        if ($access_result->isAllowed() && $entity->isDeleted()) {
          throw new NotFoundHttpException;
        }
        return $access_result;

      case 'delete':
        $access_result = AccessResult::allowedIf(($account->hasPermission('delete private conversation') && $entity->participantOf($account)))->cachePerPermissions()->addCacheableDependency($entity);
        if ($access_result->isAllowed() && $entity->isDeleted()) {
          throw new NotFoundHttpException;
        }
        return $access_result;

    }

    return AccessResult::allowed();
  }

}
