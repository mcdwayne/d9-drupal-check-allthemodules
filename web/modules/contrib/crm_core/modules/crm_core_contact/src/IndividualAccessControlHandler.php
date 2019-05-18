<?php

namespace Drupal\crm_core_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\crm_core_contact\Entity\IndividualType;

/**
 * Access control handler for CRM Core Individual entities.
 */
class IndividualAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_individual entities',
          'view any crm_core_individual entity',
          'view any crm_core_individual entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_individual entities',
          'edit any crm_core_individual entity',
          'edit any crm_core_individual entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_individual entities',
          'delete any crm_core_individual entity',
          'delete any crm_core_individual entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'revert':
        // @todo: more fine grained will be adjusting dynamic permission
        // generation for reverting bundles of individuals.
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_individual entities',
          'revert all crm_core_individual revisions',
        ], 'OR');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $individual_type_is_active = empty($entity_bundle);

    // Load the individual type entity.
    if (!empty($entity_bundle)) {
      /* @var \Drupal\crm_core_contact\Entity\IndividualType $individual_type_entity */
      $individual_type_entity = IndividualType::load($entity_bundle);
      $individual_type_is_active = $individual_type_entity->status();
    }

    return AccessResult::allowedIf($individual_type_is_active)
      ->andIf(AccessResult::allowedIfHasPermissions($account, [
        'administer crm_core_individual entities',
        'create crm_core_individual entities',
        'create crm_core_individual entities of bundle ' . $entity_bundle,
      ], 'OR'));
  }

}
