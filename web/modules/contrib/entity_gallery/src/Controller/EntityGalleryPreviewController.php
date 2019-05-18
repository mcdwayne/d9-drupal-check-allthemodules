<?php

namespace Drupal\entity_gallery\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single entity gallery in preview.
 */
class EntityGalleryPreviewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity_gallery_preview, $view_mode_id = 'full', $langcode = NULL) {
    $entity_gallery_preview->preview_view_mode = $view_mode_id;
    $build = parent::view($entity_gallery_preview, $view_mode_id);

    $build['#attached']['library'][] = 'entity_gallery/drupal.entity_gallery.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single entity gallery in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_gallery_preview
   *   The current entity gallery.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $entity_gallery_preview) {
    return $this->entityManager->getTranslationFromContext($entity_gallery_preview)->label();
  }

}
