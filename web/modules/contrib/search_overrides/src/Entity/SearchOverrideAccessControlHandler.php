<?php

namespace Drupal\search_overrides\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Search override.
 *
 * @see \Drupal\search_api_solr_elevate_exclude\Entity\SearchElevate.
 */
class SearchOverrideAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\search_overrides\Entity\SearchOverride $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published search overrides');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit search overrides');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete search overrides');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add search overrides');
  }

}
