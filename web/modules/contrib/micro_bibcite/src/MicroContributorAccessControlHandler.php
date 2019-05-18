<?php

namespace Drupal\micro_bibcite;

use Drupal\bibcite_entity\ContributorAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the bibcite contributor entity type.
 */
class MicroContributorAccessControlHandler extends ContributorAccessControlHandler {

  /**
   * Allow access to bibcite keyword label.
   *
   * @var bool
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow to view contributor label.
    // @TODO check if in a site context and permission ?
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
