<?php

namespace Drupal\external_entities\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single external entity.
 */
class ExternalEntityViewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = ['external_entities' => parent::view($entity)];

    $build['#title'] = $build['external_entities']['#title'];
    unset($build['external_entities']['#title']);

    foreach ($entity->uriRelationships() as $rel) {
      // Set the node path as the canonical URL to prevent duplicate content.
      $build['#attached']['html_head_link'][] = [
        [
          'rel' => $rel,
          'href' => $entity->toUrl($rel)->toString(),
        ],
        TRUE,
      ];

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => 'shortlink',
            'href' => $entity->toUrl($rel, ['alias' => TRUE])->toString(),
          ],
          TRUE,
        ];
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single external entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current external entity.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $entity) {
    return Html::escape($this->entityManager->getTranslationFromContext($entity)->label());
  }

}
