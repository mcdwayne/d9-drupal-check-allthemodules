<?php

namespace Drupal\mailjet_subscription;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for  Mailjet Subscription form entities.
 *
 *
 * @ingroup mailjet_subscription
 */
class SubscriptionFormController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if ($operation == 'view') {
      return TRUE;
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
