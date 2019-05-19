<?php

/**
 * @file
 * Contains \Drupal\zsm\ZSMCoreAccessControlHandler.
 */

namespace Drupal\zsm;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the zsm_core entity.
 *
 * @see \Drupal\zsm\Entity\ZSMCore.
 */
class ZSMCoreAccessControlHandler extends EntityAccessControlHandler {

    /**
     * {@inheritdoc}
     *
     * Link the activities to the permissions. checkAccess is called with the
     * $operation as defined in the routing.yml file.
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view zsm_core entity');

            case 'edit':
                return AccessResult::allowedIfHasPermission($account, 'edit zsm_core entity');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete zsm_core entity');
        }
        return AccessResult::allowed();
    }

    /**
     * {@inheritdoc}
     *
     * Separate from the checkAccess because the entity does not yet exist, it
     * will be created during the 'add' process.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
        return AccessResult::allowedIfHasPermission($account, 'add zsm_core entity');
    }

}
