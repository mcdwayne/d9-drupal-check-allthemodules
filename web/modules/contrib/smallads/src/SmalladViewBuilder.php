<?php

namespace Drupal\smallads;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Render controller for Smallads.
 */
class SmalladViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    if ($view_mode != 'default' && $view_mode != 'full') {
      $build['#theme'] = 'smallad_' . $view_mode;
    }
    $build['#attached']['library'][] = 'smallads/css';
    return $build;
  }

}
