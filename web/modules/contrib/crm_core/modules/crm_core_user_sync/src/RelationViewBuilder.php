<?php

namespace Drupal\crm_core_user_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a relation entity type.
 */
class RelationViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The relation has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
