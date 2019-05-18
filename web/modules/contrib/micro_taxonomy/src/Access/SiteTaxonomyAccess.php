<?php

namespace Drupal\micro_taxonomy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteTypeInterface;
use Drupal\micro_taxonomy\MicroTaxonomyManagerInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\Routing\Route;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Provides an access checker for site entities taxonomy overview form.
 */
class SiteTaxonomyAccess {

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The menu on which check access.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, VocabularyInterface $taxonomy_vocabulary, SiteInterface $site = NULL) {
    $site_id = $taxonomy_vocabulary->getThirdPartySetting('micro_taxonomy', 'site_id');

    if (empty($site_id)) {
      return AccessResult::forbidden('Vocabulary is not associated with a site entity')->addCacheableDependency($taxonomy_vocabulary);
    }

    if (empty($site)) {
      // Try to load it form it's id stored on the menu.
      $site = Site::load($site_id);
      if (empty($site)) {
        return AccessResult::forbidden('Site associated with the vocabulary not exists no more')->addCacheableDependency($taxonomy_vocabulary);
      }
    }

    $site_type = $site->type->entity;
    if ($site_type instanceof SiteTypeInterface) {
      $vocabularies = array_filter($site_type->getVocabularies());
      if (empty($vocabularies) && !$site->hasVocabulary()) {
        return AccessResult::forbidden('The site entity is not configured to use any taxonomy term')->addCacheableDependency($site_type);
      }
    }

    if ($site->id() != $site_id) {
      return AccessResult::forbidden('Vocabulary do not correspond to the site id')->addCacheableDependency($taxonomy_vocabulary);
    }

    if (!$site->isRegistered()) {
      return AccessResult::forbidden('Vocabulary can be managed only on site registered and so from the site url.')->addCacheableDependency($taxonomy_vocabulary)->addCacheableDependency($site);
    }

    if ($account->hasPermission('administer micro vocabularies')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('administer own micro vocabulary')) {

      /** @var \Drupal\micro_taxonomy\MicroTaxonomyManagerInterface $micro_taxonomy_manager */
      $micro_taxonomy_manager = \Drupal::service('micro_taxonomy.manager');
      if ($micro_taxonomy_manager->userCanAccessTermOverview($account, $site, MicroTaxonomyManagerInterface::ACCESS_OVERVIEW_TERM)) {
        return AccessResult::allowed()->addCacheableDependency($site)->addCacheableDependency($account)->cachePerPermissions();
      }
    }

    // No opinion, let's others module give access eventually.
    return AccessResult::neutral();
  }

}
