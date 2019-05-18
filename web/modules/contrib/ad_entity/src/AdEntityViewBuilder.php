<?php

namespace Drupal\ad_entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides the view builder for Advertising entities.
 */
class AdEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {}

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = '["any"]', $langcode = NULL) {
    /** @var \Drupal\ad_entity\Entity\AdEntityInterface $entity */
    if ($view_mode == 'default' || $view_mode == 'full') {
      $view_mode = '["any"]';
    }
    if (strpos($view_mode, '["') !== 0) {
      $view_mode = '["' . $view_mode . '"]';
    }

    // No cache keys given, because rendered Advertising entity instances
    // don't get their own cache records. The reason behind this is that
    // once the cache entry of the parent render element is being invalidated,
    // it's most likely that the cache record of this element here would be
    // invalidated as well. This procedure is not worth the I/O overhead,
    // plus rendering Advertising entities shouldn't be a resource breaker.
    // Another plus is that one instance of an Advertising entity can be
    // used more than once on a page. Regards the problem of reusable render
    // components, @see https://www.drupal.org/project/drupal/issues/2877045
    $build = [
      '#cache' => [
        'tags' => Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags()),
        'contexts' => $entity->getCacheContexts(),
        'max-age' => $entity->getCacheMaxAge(),
      ],
    ];

    // Check whether a given context wants to turn off the advertisement.
    $turnoff = $entity->getContextDataForPlugin('turnoff');
    if (!empty($turnoff)) {
      return $build;
    }

    $build += [
      '#theme' => 'ad_entity',
      '#ad_entity' => $entity,
      '#variant' => $view_mode,
    ];

    // Allow modules to modify the render array.
    $this->moduleHandler()->alter(['ad_entity_view'], $build, $entity);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = '["any"]', $langcode = NULL) {
    $build = [];
    foreach ($entities as $entity) {
      $build[$entity->id()] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
