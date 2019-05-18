<?php

namespace Drupal\entity_split;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Entity split entity.
 *
 * @see \Drupal\entity_split\Entity\EntitySplit.
 */
class EntitySplitAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entity_split\Entity\EntitySplitInterface $entity */
    $master_entity = $entity->getMasterEntity();

    return !empty($master_entity) ? $master_entity->access($operation, $account, TRUE) : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden();
  }

}
