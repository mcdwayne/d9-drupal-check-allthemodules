<?php

namespace Drupal\drd\Entity\AccessControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Requirement entity.
 *
 * @see \Drupal\drd\Entity\Requirement.
 */
class Requirement extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\drd\Entity\RequirementInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'drd.view unpublished requirement entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'drd.view published requirement entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'drd.edit requirement entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'drd.delete requirement entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'drd.add requirement entities');
  }

}
