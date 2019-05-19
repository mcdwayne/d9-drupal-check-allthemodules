<?php

namespace Drupal\smallads;

use Drupal\smallads\Entity\SmalladInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the Smallad entity.
 */
class SmalladAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $smallAd, $operation, AccountInterface $account) {
    
    if ($account->hasPermission('access administration pages')) {
      $result = AccessResult::allowed()->cachePerUser();
    }
    // Owners can do anything with their own content.
    elseif ($smallAd->getOwnerId() == $account->id() or $account->hasPermission('edit all smallads')) {
      $result = AccessResult::allowed()->cachePerUser();
    }
    elseif ($operation == 'view') {
      switch ($smallAd->scope->value) {
        case SmalladInterface::SCOPE_PUBLIC:
          //This may be search indexed. Anyone can see it.
        case SmalladInterface::SCOPE_NETWORK:
          //This may be indexed using solsearch, so anon can still see it.
          $result = AccessResult::allowed();

        case SmalladInterface::SCOPE_SITE:
          //Only members of the site can see it.
          $result = AccessResult::allowedIfHasPermission($account, 'access content');
        case SmalladInterface::SCOPE_GROUP:
          throw new \Exception('If groups are enabled SmalladAccessControlHandler should be overridden ');
        case SmalladInterface::SCOPE_PRIVATE:
          $result = AccessResult::forbidden('Small ad is private');
      }
    }
    else {
      $result = AccessResult::forbidden();
    }
    return $result->addCacheableDependency($smallAd);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'post smallad');
  }

}
