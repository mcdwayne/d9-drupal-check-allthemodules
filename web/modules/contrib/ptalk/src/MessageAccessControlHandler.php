<?php

namespace Drupal\ptalk;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Access controller for the ptalk_message entity.
 *
 * @see \Drupal\ptalk\Entity\Message.
 */
class MessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {

      case 'delete':
        // Check if account has the proper permission and belongs to the conversation.
        $access_result = AccessResult::allowedIf(($account->hasPermission('delete message private conversation') && $entity->getThread()->participantOf($account)))->cachePerPermissions()->addCacheableDependency($entity);
        // If message is already deleted throw page not found.
        if ($access_result->isAllowed() && $entity->isDeleted()) {
          throw new NotFoundHttpException;
        }
        return $access_result;

      case 'restore':
        $access_result = AccessResult::allowedIf(($account->hasPermission('restore message private conversation') && $entity->getThread()->participantOf($account)))->cachePerPermissions()->addCacheableDependency($entity);
        if ($access_result->isAllowed() && !$entity->isDeleted()) {
          throw new NotFoundHttpException;
        }
        return $access_result;

    }

    return AccessResult::allowed();
  }
}
