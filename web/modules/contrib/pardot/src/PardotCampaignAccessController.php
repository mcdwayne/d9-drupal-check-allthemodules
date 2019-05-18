<?php

namespace Drupal\pardot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the Pardot Campaign entity.
 *
 * We set this class to be the access controller in Pardot Campaign's entity annotation.
 *
 * @see \Drupal\pardot\Entity\PardotCampaign
 *
 * @ingroup pardot
 */
class PardotCampaignAccessController extends EntityAccessControlHandler {

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
