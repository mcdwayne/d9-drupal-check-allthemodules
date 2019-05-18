<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * View builder handler for quick_code_types.
 */
class QuickCodeTypeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    $entity_type_key = "#{$this->entityTypeId}";
    $view_hook = "{$this->entityTypeId}_view";

    $children = Element::children($build_list);
    foreach ($children as $key) {
      if (isset($build_list[$key][$entity_type_key])) {
        $entity = $build_list[$key][$entity_type_key];
        if ($entity instanceof QuickCodeTypeInterface) {
          $build_list[$key]['quick_codes'] = views_embed_view('quick_code', 'default', $entity->id());

          $view_mode = $build_list[$key]['#view_mode'];
          $this->moduleHandler()->invokeAll($view_hook, [&$build_list[$key], $entity, $view_mode]);
        }
      }
    }

    return $build_list;
  }

}
