<?php

namespace Drupal\commerce_epayco;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the CommerceEpaycoApiData entity.
 *
 * @ingroup commerce_epayco
 */
class CommerceEpaycoApiDataAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return parent::checkAccess($entity, $operation, $account);
  }

}
