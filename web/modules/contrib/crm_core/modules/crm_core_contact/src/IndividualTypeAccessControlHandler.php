<?php

namespace Drupal\crm_core_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for CRM Core Individual type entities.
 */
class IndividualTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\crm_core_contact\Entity\IndividualType $entity */

    // First check permission.
    if (parent::checkAccess($entity, $operation, $account)->isForbidden()) {
      return AccessResult::forbidden();
    }

    switch ($operation) {
      case 'delete':
        // If individual instance of this individual type exist, you can't
        // delete it.
        $results = \Drupal::entityQuery('crm_core_individual')
          ->condition('type', $entity->id())
          ->execute();
        return AccessResult::allowedIf(empty($results));

      // @todo Which is it?
      case 'view':
      case 'edit':
      case 'update':
        // If the individual type is locked, you can't edit it.
        return AccessResult::allowed();
    }
  }

}
