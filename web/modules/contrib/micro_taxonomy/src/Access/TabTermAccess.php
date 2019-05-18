<?php

namespace Drupal\micro_taxonomy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\Entity\SiteTypeInterface;
use Drupal\micro_taxonomy\MicroTaxonomyManagerInterface;

/**
 * Check access on the site taxonomy term view tab.
 */
class TabTermAccess {

  public function access(AccountInterface $account, SiteInterface $site = NULL) {

    if ($site instanceof SiteInterface) {

      $site_type = $site->type->entity;
      if ($site_type instanceof SiteTypeInterface) {
        $vocabularies = array_filter($site_type->getVocabularies());
        if (empty($vocabularies) && !$site->hasVocabulary()) {
          return AccessResult::forbidden('The site entity is not configured to use any taxonomy term')->addCacheableDependency($site_type);
        }
      }

      if ($account->hasPermission('administer site entities')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      if(!$site->isRegistered()) {
        return AccessResult::neutral('Site tab term can be access only on site registered.');
      }

      /** @var \Drupal\micro_taxonomy\MicroTaxonomyManagerInterface $micro_taxonomy_manager */
      $micro_taxonomy_manager = \Drupal::service('micro_taxonomy.manager');
      if ($micro_taxonomy_manager->userCanAccessTermOverview($account, $site, MicroTaxonomyManagerInterface::ACCESS_TAB_TERM)) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }
    return AccessResult::neutral('Using this route can only be done in a site context.');
  }

}
