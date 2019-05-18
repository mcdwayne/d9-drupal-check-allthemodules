<?php

namespace Drupal\micro_node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\system\MenuInterface;
use Drupal\micro_site\SiteUsers;

/**
 * Check access on custom route for adding node on a site entity.
 */
class NodeAddAccess {

  public function access(AccountInterface $account, SiteInterface $site = NULL, NodeTypeInterface $node_type = NULL) {

    if ($node_type && $site) {
      $node_types = \Drupal::config('micro_node.settings')->get('node_types');
      /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
      $site_type = $site->type->entity;
      // Site type can filter the node types enabled globaly on the site.
      $site_node_types = array_filter($site_type->getTypes());

      if (in_array($node_type->id(), $node_types) && in_array($node_type->id(), $site_node_types)) {

        if ($account->hasPermission('administer site entities')) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        if(!$site->isRegistered()) {
          return AccessResult::neutral('Content can be create only on site registered.');
        }

        /** @var \Drupal\micro_node\MicroNodeManagerInterface $micro_node_manager */
        $micro_node_manager = \Drupal::service('micro_node.manager');
        // Site administrators and owner can always add content to their site
        if ($micro_node_manager->userCanCreateContent($account, $site)) {
          return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
        }

      }

    }

    return AccessResult::neutral('Using this route can only be done in a site context');
  }

}
