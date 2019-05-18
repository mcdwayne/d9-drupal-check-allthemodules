<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorConfigAccessControlHandler.
 */

namespace Drupal\monitoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for sensor config.
 *
 * @see \Drupal\monitoring\Entity\SensorConfig
 */
class SensorConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\monitoring\Entity\SensorConfig $entity */
    $plugin_definition = $entity->getPlugin()->getPluginDefinition();

    if ($operation == 'delete' && !$plugin_definition['addable']) {
      return AccessResult::forbidden();
    }
    if ($operation == 'view') {
      if (!$entity->isEnabled()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }
      elseif ($account->hasPermission('monitoring reports')) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return parent::checkAccess($entity, $operation, $account);
  }
}
