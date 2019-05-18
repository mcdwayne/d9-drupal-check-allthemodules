<?php

namespace Drupal\preview_link\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Preview link controller to view any entity.
 */
class PreviewLinkController extends ControllerBase {

  /**
   * Preview any entity with the default view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   A render array for previewing the entity.
   */
  public function preview(EntityInterface $entity) {
    return $this->entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity);
  }

  /**
   * Preview page title.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The title of the entity.
   */
  public function title(EntityInterface $entity) {
    return $entity->label();
  }

}
