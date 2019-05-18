<?php

namespace Drupal\micro_bibcite;

use Drupal\bibcite_entity\KeywordAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the bibcite keyword entity type.
 */
class MicroKeywordAccessControlHandler extends KeywordAccessControlHandler {

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
    // Allow to view keyword label.
    // @TODO check if in a site context and permission ?
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
