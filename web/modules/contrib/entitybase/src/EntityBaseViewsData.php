<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseViewsData.
 */

namespace Drupal\entity_base;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the entity type.
 */
class EntityBaseViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    return $data;
  }

}
