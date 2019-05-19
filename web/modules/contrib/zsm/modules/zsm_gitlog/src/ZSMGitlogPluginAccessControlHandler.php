<?php

/**
 * @file
 * Contains \Drupal\zsm_gitlog\ZSMGitlogPluginAccessControlHandler.
 */

namespace Drupal\zsm_gitlog;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the zsm_gitlog_plugin entity.
 *
 * @see \Drupal\zsm_gitlog\Entity\ZSMGitlogPlugin.
 */
class ZSMGitlogPluginAccessControlHandler extends EntityAccessControlHandler {

    /**
     * {@inheritdoc}
     *
     * Link the activities to the permissions. checkAccess is called with the
     * $operation as defined in the routing.yml file.
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view zsm_gitlog_plugin entity');

            case 'edit':
                return AccessResult::allowedIfHasPermission($account, 'edit zsm_gitlog_plugin entity');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete zsm_gitlog_plugin entity');
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
        return AccessResult::allowedIfHasPermission($account, 'add zsm_gitlog_plugin entity');
    }

}
