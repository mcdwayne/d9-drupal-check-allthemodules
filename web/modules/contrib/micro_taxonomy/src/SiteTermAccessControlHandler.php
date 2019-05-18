<?php

namespace Drupal\micro_taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\taxonomy\TermAccessControlHandler;

/**
 * Defines the access control handler for the taxonomy term entity type in the
 * context of a micro site.
 */
class SiteTermAccessControlHandler extends TermAccessControlHandler {

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The micro taxonomy manager.
   *
   * @var \Drupal\micro_taxonomy\MicroTaxonomyManagerInterface
   */
  protected $taxonomyManager;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $active_site = $this->negotiator()->getActiveSite();
    $term_site = $this->taxonomyManager()->getSite($entity);
    $is_on_all_site = $this->taxonomyManager()->isAvailableOnAllSites($entity);

    if ($active_site instanceof SiteInterface) {
      switch ($operation) {
        case 'view':
          if ($this->sitesMatch($active_site, $term_site)) {
            $access_result = AccessResult::allowedIf($account->hasPermission('access content') && $entity->isPublished())
              ->cachePerPermissions()
              ->addCacheableDependency($entity);
          }
          elseif (!$term_site && $is_on_all_site) {
            $access_result = AccessResult::allowedIf($account->hasPermission('access content') && $entity->isPublished())
              ->cachePerPermissions()
              ->addCacheableDependency($entity);
          }
          else {
            $access_result = AccessResult::forbidden('The term do not correspond to the active site.');
          }
          return $access_result;

        case 'update':
          if ($this->sitesMatch($active_site, $term_site)) {
           $can_update = $this->taxonomyManager()->userCanUpdateTerm($account, $active_site, MicroTaxonomyManagerInterface::UPDATE_TERM);
            $access_result = AccessResult::allowedIf($can_update || $account->hasPermission('administer taxonomy'))
              ->addCacheableDependency($entity)
              ->addCacheableDependency($active_site);
          }
          else {
            $access_result = AccessResult::forbidden('The term is editable only on its the active site.')
              ->addCacheableDependency($active_site);
          }

          return $access_result;

        case 'delete':
          if ($this->sitesMatch($active_site, $term_site)) {
            $can_delete = $this->taxonomyManager()->userCanDeleteTerm($account, $active_site, MicroTaxonomyManagerInterface::DELETE_TERM);
            $access_result = AccessResult::allowedIf($can_delete || $account->hasPermission('administer taxonomy'))
              ->addCacheableDependency($entity)
              ->addCacheableDependency($active_site);
          }
          else {
            $access_result = AccessResult::forbidden('The term is deletable only on the active site.')
              ->addCacheableDependency($active_site);
          }
          return $access_result;

        default:
          // No opinion.
          return AccessResult::neutral()->cachePerPermissions();
      }
    }

    // We are on the master host. See only the terms without site_id.
    else {
      switch ($operation) {
        case 'view':
          if ($term_site || $is_on_all_site) {
            return AccessResult::forbidden('The term is owned by a micro site or available for all micro sites.');
          }
          else {
            return parent::checkAccess($entity, $operation, $account);
          }
          break;
        case 'update':
        case 'delete':
          if ($term_site || $is_on_all_site) {
            return AccessResult::allowedIfHasPermission($account, 'administer taxonomy');
          }
          else {
            return parent::checkAccess($entity, $operation, $account);
          }
          break;
        default:
          return AccessResult::forbidden('Deny all by default for term in a micro site instance.');
          break;
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $active_site = $this->negotiator()->getActiveSite();
    if ($active_site instanceof SiteInterface) {
      $can_create = $this->taxonomyManager()->userCanCreateTerm($account, $active_site, MicroTaxonomyManagerInterface::CREATE_TERM);
      return AccessResult::allowedIf($can_create || $account->hasPermission('administer taxonomy'));
    }
    else {
      return parent::checkCreateAccess($account, $context, $entity_bundle);
    }
  }

  /**
   * Check if two site entities are the same.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $active_site
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $term_site
   *
   * @return bool
   */
  protected function sitesMatch(SiteInterface $active_site = NULL, SiteInterface $term_site = NULL) {
    return $active_site instanceof SiteInterface && $term_site instanceof SiteInterface && $active_site->id() == $term_site->id();
  }

  /**
   * Gets the site negotiator.
   *
   * @return \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   */
  protected function negotiator() {
    if (!$this->negotiator) {
      $this->negotiator = \Drupal::service('micro_site.negotiator');
    }
    return $this->negotiator;
  }

  /**
   * Sets the site negotiator for this handler.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   *
   * @return $this
   */
  protected function setNegotiator(SiteNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
    return $this;
  }

  /**
   * Gets the micro taxonomy manager.
   *
   * @return \Drupal\micro_taxonomy\MicroTaxonomyManagerInterface
   *   The micro taxonomy manager.
   */
  protected function taxonomyManager() {
    if (!$this->taxonomyManager) {
      $this->taxonomyManager = \Drupal::service('micro_taxonomy.manager');
    }
    return $this->taxonomyManager;
  }

  /**
   * Sets the micro taxonomy manager for this handler.
   *
   * @param \Drupal\micro_taxonomy\MicroTaxonomyManagerInterface
   *   The micro taxonomy manager.
   *
   * @return $this
   */
  public function setTaxonomyManager(MicroTaxonomyManagerInterface $taxonomy_manager) {
    $this->taxonomyManager = $taxonomy_manager;
    return $this;
  }

}
