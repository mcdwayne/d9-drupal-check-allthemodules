<?php

namespace Drupal\box;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\box\Entity\Box;

/**
 * View builder handler for boxes.
 */
class BoxViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\box\Entity\Box[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      // Add Language field text element to box render array.
      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = [
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => $entity->language()->getName(),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\box\Entity\BoxInterface $entity */
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
