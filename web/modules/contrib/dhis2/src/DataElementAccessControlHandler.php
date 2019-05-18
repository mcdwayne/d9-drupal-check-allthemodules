<?php

namespace Drupal\dhis;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Data element entity.
 *
 * @see \Drupal\dhis\Entity\DataElement.
 */
class DataElementAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\dhis\Entity\DataElementInterface $entity */
        switch ($operation) {
            case 'view':
                if (!$entity->isPublished()) {
                    return AccessResult::allowedIfHasPermission($account, 'view unpublished data element entities');
                }
                return AccessResult::allowedIfHasPermission($account, 'view published data element entities');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit data element entities');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete data element entities');
        }

        // Unknown operation, no opinion.
        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'add data element entities');
    }

}
