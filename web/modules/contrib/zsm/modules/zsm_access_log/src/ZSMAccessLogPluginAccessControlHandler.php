<?php

/**
 * @file
 * Contains \Drupal\zsm_access_log\ZSMAccessLogPluginAccessControlHandler.
 */

namespace Drupal\zsm_access_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the zsm_access_log_plugin entity.
 *
 * @see \Drupal\zsm_access_log\Entity\ZSMAccessLogPlugin.
 */
class ZSMAccessLogPluginAccessControlHandler extends EntityAccessControlHandler {

    /**
     * {@inheritdoc}
     *
     * Link the activities to the permissions. checkAccess is called with the
     * $operation as defined in the routing.yml file.
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view zsm_access_log_plugin entity');

            case 'edit':
                return AccessResult::allowedIfHasPermission($account, 'edit zsm_access_log_plugin entity');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete zsm_access_log_plugin entity');
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
        return AccessResult::allowedIfHasPermission($account, 'add zsm_access_log_plugin entity');
    }

}
