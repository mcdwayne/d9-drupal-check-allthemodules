<?php

namespace Drupal\widget_engine_domain_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\widget_engine\WidgetAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Widget entity with domain-based restrictions.
 *
 * @see \Drupal\widget_engine\Entity\Widget.
 */
class WidgetDomainAccessControlHandler extends WidgetAccessControlHandler {

  /**
   * Domain access manager.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $domainAccessManager;

  /**
   * Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * WidgetDomainAccessControlHandler constructor.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->domainAccessManager = \Drupal::service('domain_access.manager');
    $this->domainNegotiator = \Drupal::service('domain.negotiator');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $allowed = FALSE;
    if ($account->hasPermission('add widget entities')) {
      $allowed = TRUE;
    }
    elseif (!empty($entity_bundle)) {
      $current_domain = $this->domainNegotiator->getActiveDomain();
      $allowed = $this->domainAccessManager
        ->hasDomainPermissions($account, $current_domain, ["create $entity_bundle widget on assigned domains"]);
    }

    if ($allowed) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $entity_is_accessible = $this->domainAccessManager->checkEntityAccess($entity, $account);
    $allowed = FALSE;
    /** @var \Drupal\widget_engine\Entity\WidgetInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          if ($account->hasPermission('view unpublished widget entities')
            || ($entity_is_accessible && $account->hasPermission('view unpublished domain widgets'))) {
            $allowed = TRUE;
          }
        }
        elseif ($account->hasPermission('view published widget entities')) {
          $allowed = TRUE;
        }
        break;

      case 'update':
        if ($account->hasPermission('edit widget entities')
          || ($entity_is_accessible && $account->hasPermission('edit domain widgets'))
          || ($entity_is_accessible && $account->hasPermission('update ' . $entity->bundle() . ' widget on assigned domains'))) {
          $allowed = TRUE;
        }
        break;

      case 'delete':
        if ($account->hasPermission('delete widget entities')
          || ($entity_is_accessible && $account->hasPermission('delete ' . $entity->bundle() . ' widget on assigned domains'))) {
          $allowed = TRUE;
        }
        break;

      default:
    }

    if ($allowed) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

}
