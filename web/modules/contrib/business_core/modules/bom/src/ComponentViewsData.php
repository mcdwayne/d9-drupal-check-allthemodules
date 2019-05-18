<?php

namespace Drupal\bom;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the bom component entity types.
 */
class ComponentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['bom_component']['bom']['argument']['id'] = 'bom';

    return $data;
  }

}
