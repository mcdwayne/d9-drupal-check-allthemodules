<?php

namespace Drupal\micro_node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\Entity\SiteTypeInterface;

/**
 * Check access on the site content tab.
 */
class TabContentAccess {

  public function access(AccountInterface $account, SiteInterface $site = NULL) {

    if ($site instanceof SiteInterface) {

      $site_type = $site->type->entity;
      if ($site_type instanceof SiteTypeInterface) {
        $node_types = $site_type->getTypes();
        if (empty($node_types)) {
          return AccessResult::forbidden('The site entity is not configured to have any content')->addCacheableDependency($site_type);
        }
      }

      if ($account->hasPermission('administer site entities')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      if(!$site->isRegistered()) {
        return AccessResult::neutral('Site tab content can be access only on site registered.');
      }

      /** @var \Drupal\micro_node\MicroNodeManagerInterface $micro_node_manager */
      $micro_node_manager = \Drupal::service('micro_node.manager');
      if ($micro_node_manager->userCanAccessContentOverview($account, $site)) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }
    return AccessResult::neutral('Using this route can only be done in a site context.');
  }

}
