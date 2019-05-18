<?php

namespace Drupal\pardot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the Pardot Contact Form Map entity.
 *
 * We set this class to be the access controller in Pardot Contact Form Map's entity annotation.
 *
 * @see \Drupal\pardot\Entity\PardotContactFormMap
 *
 * @ingroup pardot
 */
class PardotContactFormMapAccessController extends EntityAccessControlHandler {

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
