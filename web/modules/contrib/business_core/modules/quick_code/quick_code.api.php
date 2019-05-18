<?php

/**
 * Act on quick_code_type entities assembled before rendering.
 *
 * @param &$build
 *   A renderable array representing the entity content. The module may add
 *   elements to $build prior to rendering. The structure of $build is a
 *   renderable array as expected by drupal_render().
 * @param \Drupal\quick_code\QuickCodeTypeInterface $entity
 *   The entity object.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 */
function hook_quick_code_type_view(array &$build, \Drupal\quick_code\QuickCodeTypeInterface $entity, $view_mode) {
  if ($entity->id() == 'drug') {
    $build['quick_codes'] = views_embed_view('category_drug');
  }
}
