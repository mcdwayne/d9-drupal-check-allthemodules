<?php

namespace Drupal\drd\Entity\AccessControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Domain entity.
 *
 * @see \Drupal\drd\Entity\Domain.
 */
class Domain extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\drd\Entity\DomainInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'drd.view unpublished domain entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'drd.view published domain entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'drd.edit domain entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'drd.delete domain entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'drd.add domain entities');
  }

}
