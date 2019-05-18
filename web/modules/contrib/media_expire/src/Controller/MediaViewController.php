<?php

namespace Drupal\media_expire\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Extends EntityViewController to overwrite the view method.
 */
class MediaViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $media, $view_mode = 'full') {
    $page = $this->entityManager
      ->getViewBuilder($media->getEntityTypeId())
      ->view($media, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $media->getEntityTypeId();

    if (empty($page['#' . $page['#entity_type']])) {
      $page['#' . $page['#entity_type']] = $media;
    }

    return $page;
  }

}
