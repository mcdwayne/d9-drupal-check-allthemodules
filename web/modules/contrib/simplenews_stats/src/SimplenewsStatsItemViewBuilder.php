<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a simplenews stats entity type.
 */
class SimplenewsStatsItemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The simplenews stats has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
