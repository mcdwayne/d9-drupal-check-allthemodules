<?php

namespace Drupal\micro_bibcite\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\system\MenuInterface;
use Drupal\micro_site\SiteUsers;

/**
 * Check access on the micro bibcite action links.
 */
class MicroBibciteAccess {

  public function access(AccountInterface $account, SiteInterface $site = NULL) {

    if ($site instanceof SiteInterface) {

      if(!$site->isRegistered()) {
        return AccessResult::neutral('Micro bibcite routes can be access only on site registered.');
      }

      if ($account->hasPermission('administer site entities')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      /** @var \Drupal\micro_bibcite\MicroBibciteManagerInterface $micro_bibcite_manager */
      $micro_bibcite_manager = \Drupal::service('micro_bibcite.manager');
      if ($micro_bibcite_manager->userCanDoOperation($account, $site, 'create')) {
        return AccessResult::allowedIfHasPermission($account, 'administer micro bibcite')->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }
    return AccessResult::neutral('Using this route can only be done in a site context.');
  }

  public function accessPopulate(AccountInterface $account, SiteInterface $site = NULL) {

    if ($site instanceof SiteInterface) {

      if(!$site->isRegistered()) {
        return AccessResult::neutral('Micro bibcite routes can be access only on site registered.');
      }

      if ($account->hasPermission('administer bibcite')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      /** @var \Drupal\micro_bibcite\MicroBibciteManagerInterface $micro_bibcite_manager */
      $micro_bibcite_manager = \Drupal::service('micro_bibcite.manager');
      if ($micro_bibcite_manager->userCanDoOperation($account, $site, 'create')) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }
    return AccessResult::neutral('Using this route can only be done in a site context.');
  }

  public function accessImport(AccountInterface $account, SiteInterface $site = NULL) {

    if ($site instanceof SiteInterface) {

      if(!$site->isRegistered()) {
        return AccessResult::neutral('Micro bibcite routes can be access only on site registered.');
      }

      if ($account->hasPermission('administer bibcite')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      /** @var \Drupal\micro_bibcite\MicroBibciteManagerInterface $micro_bibcite_manager */
      $micro_bibcite_manager = \Drupal::service('micro_bibcite.manager');
      if ($micro_bibcite_manager->userCanDoOperation($account, $site, 'create')) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }
    return AccessResult::neutral('Using this route can only be done in a site context.');
  }

}
