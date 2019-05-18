<?php

/**
 * @file
 * Contains \Drupal\block_page\Entity\BlockPageAccess.
 */

namespace Drupal\block_page\Entity;

use Drupal\block_page\Plugin\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the block page entity type.
 */
class BlockPageAccess extends EntityAccessController {

  use ConditionAccessResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var $entity \Drupal\block_page\BlockPageInterface */
    if ($operation == 'view') {
      return $this->resolveConditions($entity->getAccessConditions(), $entity->getAccessLogic(), $entity->getContexts());
    }
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
