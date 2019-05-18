<?php

namespace Drupal\domain_menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\MenuLinkContentAccessControlHandler;

/**
 *
 */
class DomainMenuLinkContentAccessControlHandler extends MenuLinkContentAccessControlHandler {

  /**
   * @inheritDoc
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // There is no direct viewing of a menu link, but still for purposes of
        // content_translation we need a generic way to check access.
        return AccessResult::allowedIfHasPermissions($account, [
            'administer menu',
            'administer domain menus',
            ], 'OR'
        );

      case 'update':
        if (!($account->hasPermission('administer menu') ||
          $account->hasPermission('administer domain menus'))
        ) {
          return AccessResult::neutral()->cachePerPermissions();
        }
        else {
          // If there is a URL, this is an external link so always accessible.
          $access = AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
          /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
          // We allow access, but only if the link is accessible as well.
          $url_object = $entity->getUrlObject();
          if ($url_object && $url_object->isRouted()) {
            $link_access = $this->accessManager->checkNamedRoute($url_object->getRouteName(), $url_object->getRouteParameters(), $account, TRUE);
            $access = $access->andIf($link_access);
          }
          return $access;
        }

      case 'delete':
        return AccessResult::allowedIf(!$entity->isNew() && $account->hasPermission('administer menu'))
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
    }
  }

}
