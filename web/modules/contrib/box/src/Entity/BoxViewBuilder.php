<?php

namespace Drupal\box\Entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for boxes.
 */
class BoxViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\box\entity\BoxInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);
    if ($entity->id()) {
      if ($entity->isDefaultRevision()) {
        $build['#contextual_links']['box'] = [
          'route_parameters' => ['box' => $entity->id()],
          'metadata' => ['changed' => $entity->getChangedTime()],
        ];
      }
      else {
        $build['#contextual_links']['box_revision'] = [
          'route_parameters' => [
            'box' => $entity->id(),
            'box_revision' => $entity->getRevisionId(),
          ],
          'metadata' => ['changed' => $entity->getChangedTime()],
        ];
      }
    }
  }

}
