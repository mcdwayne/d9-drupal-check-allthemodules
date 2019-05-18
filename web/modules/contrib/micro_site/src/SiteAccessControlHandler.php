<?php

namespace Drupal\micro_site;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Site entity.
 *
 * @see \Drupal\micro_site\Entity\Site.
 */
class SiteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\micro_site\Entity\SiteInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished() && $account->hasPermission('administer sites entities')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if (!$entity->isPublished() && ($entity->getOwnerId() == $account->id() || in_array($account->id(), $entity->getUsersId(SiteUsers::MICRO_SITE_ADMINISTRATOR))) ) {
          return AccessResult::allowedIfHasPermission($account, 'view own unpublished site entity');
        }
        if (!$entity->isPublished() &&
          (in_array($account->id(), $entity->getUsersId(SiteUsers::MICRO_SITE_ADMINISTRATOR)) || in_array($account->id(), $entity->getUsersId(SiteUsers::MICRO_SITE_MANAGER))) ) {
          return AccessResult::allowedIfHasPermission($account, 'view own unpublished site entity');
        }
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished site entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published site entities');

      case 'update':
        if ($entity->getOwnerId() == $account->id() || in_array($account->id(), $entity->getUsersId(SiteUsers::MICRO_SITE_ADMINISTRATOR))) {
          return AccessResult::allowedIfHasPermission($account, 'edit own site entity');
        }
        return AccessResult::allowedIfHasPermission($account, 'edit site entities');

      case 'delete':
        if ($entity->getOwnerId() == $account->id() || in_array($account->id(), $entity->getUsersId(SiteUsers::MICRO_SITE_ADMINISTRATOR))) {
          return AccessResult::allowedIfHasPermission($account, 'delete own site entity');
        }
        return AccessResult::allowedIfHasPermission($account, 'delete site entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add site entities');
  }

}
