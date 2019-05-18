<?php

namespace Drupal\entity_delete_op\Plugin\Action\Derivative;

use Drupal\entity_delete_op\EntityDeletableInterface;
use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an action deriver for finding entity types supported.
 */
class DeletableEntityActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    if ($entity_type->get('entity_delete_op') && $entity_type->entityClassImplements(EntityDeletableInterface::class)) {
      return TRUE;
    }
    return FALSE;
  }

}
