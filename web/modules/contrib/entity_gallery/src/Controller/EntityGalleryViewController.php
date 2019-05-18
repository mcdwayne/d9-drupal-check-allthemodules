<?php

namespace Drupal\entity_gallery\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single entity gallery.
 */
class EntityGalleryViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity_gallery, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity_gallery, $view_mode, $langcode);

    foreach ($entity_gallery->uriRelationships() as $rel) {
      // Set the entity gallery path as the canonical URL to prevent duplicate
      // content.
      $build['#attached']['html_head_link'][] = array(
        array(
          'rel' => $rel,
          'href' => $entity_gallery->url($rel),
        ),
        TRUE,
      );

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $entity_gallery->url($rel, array('alias' => TRUE)),
          ),
          TRUE,
        );
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single entity gallery.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_gallery
   *   The current entity gallery.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $entity_gallery) {
    return $this->entityManager->getTranslationFromContext($entity_gallery)->label();
  }

}
