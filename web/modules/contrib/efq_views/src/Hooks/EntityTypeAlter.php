<?php

/**
 * @file
 * Contains \Drupal\efq_views\Hooks\EntityTypeAlter.
 */

namespace Drupal\efq_views\Hooks;

use Drupal\efq_views\EqViewsData;

class EntityTypeAlter {

  /**
   * Implements hook_entity_type_alter().
   */
  public function alter(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    // Replace all used views data handlers with a custom one.
    // @todo Decide the right strategy here.
    foreach ($entity_types as $entity_type) {
      if ($entity_type->hasHandlerClass('views_data')) {
        $entity_type->setHandlerClass('views_data', EqViewsData::class);
      }
    }
  }

}
