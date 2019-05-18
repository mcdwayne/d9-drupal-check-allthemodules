<?php

namespace Drupal\cg_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a transaction entity type.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The transaction has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
