<?php

namespace Drupal\path_file;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Path file entity entity.
 *
 * @see \Drupal\path_file\Entity\PathFileEntity.
 */
class PathFileEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // @var \Drupal\path_file\Entity\PathFileEntityInterface $entity

    // Default to Unknown operation, no opinion.
    $access_result = AccessResult::neutral();

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          $access_result =  AccessResult::allowedIfHasPermission($account, 'view unpublished path file entity entities');
        }else{
          $access_result = AccessResult::allowedIfHasPermission($account, 'view published path file entity entities');
        }
        break;

      case 'update':
        $access_result = AccessResult::allowedIfHasPermission($account, 'edit path file entity entities');
        break;
      case 'delete':
        $access_result =  AccessResult::allowedIfHasPermission($account, 'delete path file entity entities');
        break;
    }
    // Add Cache contexts.
    $access_result->cachePerPermissions();
    $access_result->addCacheableDependency($entity);

    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add path file entity entities');
  }

}
