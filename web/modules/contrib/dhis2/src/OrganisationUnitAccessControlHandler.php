<?php

namespace Drupal\dhis;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Organisation unit entity.
 *
 * @see \Drupal\dhis\Entity\OrganisationUnit.
 */
class OrganisationUnitAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\dhis\Entity\OrganisationUnitInterface $entity */
        switch ($operation) {
            case 'view':
                if (!$entity->isPublished()) {
                    return AccessResult::allowedIfHasPermission($account, 'view unpublished organisation unit entities');
                }
                return AccessResult::allowedIfHasPermission($account, 'view published organisation unit entities');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit organisation unit entities');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete organisation unit entities');
        }

        // Unknown operation, no opinion.
        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'add organisation unit entities');
    }

}
