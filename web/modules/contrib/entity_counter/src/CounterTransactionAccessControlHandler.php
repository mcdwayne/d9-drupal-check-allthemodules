<?php

namespace Drupal\entity_counter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;

/**
 * Defines the access control handler for the entity counter transaction entity.
 */
class CounterTransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    $entity_counter = \Drupal::routeMatch()->getParameter('entity_counter');
    // Only counters with manual sources can access. This blocks the add form.
    if ($entity_counter && !$entity_counter->hasManualSources()) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
