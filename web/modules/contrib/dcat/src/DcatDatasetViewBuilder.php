<?php

namespace Drupal\dcat;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for datasets.
 */
class DcatDatasetViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);
    if ($entity->id()) {
      $build['#contextual_links']['dcat_dataset'] = [
        'route_parameters' => ['dcat_dataset' => $entity->id()],
      ];
    }
  }

}
