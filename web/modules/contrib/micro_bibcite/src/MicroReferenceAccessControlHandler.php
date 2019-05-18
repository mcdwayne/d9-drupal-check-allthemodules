<?php

namespace Drupal\micro_bibcite;

use Drupal\bibcite_entity\ReferenceAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * Defines the access control handler for the bibcite reference entity type in the
 * context of a micro site.
 */
class MicroReferenceAccessControlHandler extends ReferenceAccessControlHandler {

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The micro taxonomy manager.
   *
   * @var \Drupal\micro_bibcite\MicroBibciteManagerInterface
   */
  protected $microBibciteManager;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $active_site = $this->negotiator()->getActiveSite();
    $entity_site = $this->microBibciteManager()->getSite($entity);

    if ($active_site instanceof SiteInterface) {
      switch ($operation) {
        case 'view':
          if ($this->sitesMatch($active_site, $entity_site)) {
            $access_result = AccessResult::allowedIf($account->hasPermission('view bibcite reference micro'))
              ->cachePerPermissions()
              ->addCacheableDependency($entity);
          }
          else {
            $access_result = AccessResult::forbidden('The reference do not correspond to the active site.');
          }
          return $access_result;

        case 'update':
          if ($this->sitesMatch($active_site, $entity_site)) {
           $can_update = $this->microBibciteManager()->userCanDoOperation($account, $active_site, $operation);
            $access_result = AccessResult::allowedIf($can_update || $account->hasPermission('administer bibcite') || $account->hasPermission('administer micro bibcite'))
              ->addCacheableDependency($entity)
              ->addCacheableDependency($active_site);
          }
          else {
            $access_result = AccessResult::forbidden('The reference is editable only on its the active site.')
              ->addCacheableDependency($active_site);
          }

          return $access_result;

        case 'delete':
          if ($this->sitesMatch($active_site, $entity_site)) {
            $can_delete = $this->microBibciteManager()->userCanDoOperation($account, $active_site, $operation);
            $access_result = AccessResult::allowedIf($can_delete || $account->hasPermission('administer bibcite') || $account->hasPermission('administer micro bibcite'))
              ->addCacheableDependency($entity)
              ->addCacheableDependency($active_site);
          }
          else {
            $access_result = AccessResult::forbidden('The reference is deletable only on the active site.')
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
          if ($entity_site) {
            return AccessResult::allowedIfHasPermissions($account, ['administer bibcite', 'view bibcite reference master']);
          }
          else {
            return parent::checkAccess($entity, $operation, $account);
          }
          break;
        case 'update':
        case 'delete':
          if ($entity_site ) {
            return AccessResult::allowedIfHasPermission($account, 'administer bibcite');
          }
          else {
            return parent::checkAccess($entity, $operation, $account);
          }
          break;
        default:
          return AccessResult::forbidden('Deny all by default for reference in a micro site instance.');
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
      $can_create = $this->microBibciteManager()->userCanDoOperation($account, $active_site, 'create');
      return AccessResult::allowedIf($can_create || $account->hasPermission('administer bibcite') || $account->hasPermission('administer micro bibcite'));
    }
    else {
      return parent::checkCreateAccess($account, $context, $entity_bundle);
    }
  }

  /**
   * Check if two site entities are the same.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $active_site
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $entity_site
   *
   * @return bool
   */
  protected function sitesMatch(SiteInterface $active_site = NULL, SiteInterface $entity_site = NULL) {
    return $active_site instanceof SiteInterface && $entity_site instanceof SiteInterface && $active_site->id() == $entity_site->id();
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
   * Gets the micro bibcite manager.
   *
   * @return \Drupal\micro_bibcite\MicroBibciteManagerInterface
   *   The micro bibcite manager.
   */
  protected function microBibciteManager() {
    if (!$this->microBibciteManager) {
      $this->microBibciteManager = \Drupal::service('micro_bibcite.manager');
    }
    return $this->microBibciteManager;
  }

  /**
   * Sets the micro taxonomy manager for this handler.
   *
   * @param \Drupal\micro_bibcite\MicroBibciteManagerInterface
   *   The micro bibcite manager.
   *
   * @return $this
   */
  public function setTaxonomyManager(MicroBibciteManagerInterface $micro_bibcite_manager) {
    $this->microBibciteManager = $micro_bibcite_manager;
    return $this;
  }

}
