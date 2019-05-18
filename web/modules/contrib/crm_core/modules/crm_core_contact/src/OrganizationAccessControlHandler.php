<?php

namespace Drupal\crm_core_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\crm_core_contact\Entity\OrganizationType;

/**
 * Access control handler for CRM Core Organization entities.
 */
class OrganizationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_organization entities',
          'view any crm_core_organization entity',
          'view any crm_core_organization entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_organization entities',
          'edit any crm_core_organization entity',
          'edit any crm_core_organization entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_organization entities',
          'delete any crm_core_organization entity',
          'delete any crm_core_organization entity of bundle ' . $entity->bundle(),
        ], 'OR');

      case 'revert':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer crm_core_organization entities',
          'revert organization record',
        ], 'OR');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $organization_type_is_active = empty($entity_bundle);

    // Load the organization type entity.
    if (!empty($entity_bundle)) {
      /* @var \Drupal\crm_core_contact\Entity\ContactType $contact_type_entity */
      $organization_type_entity = OrganizationType::load($entity_bundle);
      $organization_type_is_active = $organization_type_entity->status();
    }

    return AccessResult::allowedIf($organization_type_is_active)
      ->andIf(AccessResult::allowedIfHasPermissions($account, [
        'administer crm_core_organization entities',
        'create crm_core_organization entities',
        'create crm_core_organization entities of bundle ' . $entity_bundle,
      ], 'OR'));
  }

}
