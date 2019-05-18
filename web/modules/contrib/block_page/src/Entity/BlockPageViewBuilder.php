<?php

/**
 * @file
 * Contains \Drupal\block_page\Entity\BlockPageViewBuilder.
 */

namespace Drupal\block_page\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view builder for block pages.
 */
class BlockPageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = array();
    /** @var $entity \Drupal\block_page\BlockPageInterface */
    if ($page_variant = $entity->selectPageVariant()) {
      $build = $page_variant->render();
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    $build = array();
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
