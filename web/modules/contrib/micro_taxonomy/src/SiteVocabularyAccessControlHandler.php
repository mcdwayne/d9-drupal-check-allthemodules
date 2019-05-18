<?php

namespace Drupal\micro_taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\VocabularyAccessControlHandler;

/**
 * Defines the access control handler for the taxonomy vocabulary entity type.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
class SiteVocabularyAccessControlHandler extends VocabularyAccessControlHandler {

  /**
   * Allow access to vocabulary label.
   *
   * @var bool
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow to view vocabulary label.
    // @TODO check if in a site context and permission ?
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
