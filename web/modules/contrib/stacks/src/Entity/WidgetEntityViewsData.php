<?php

namespace Drupal\stacks\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Widget Entity entities.
 */
class WidgetEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['widget_entity']['table']['base'] = [
      'field' => 'id',
      'title' => t('Widget Entity'),
      'help' => t('The Widget Entity ID.'),
    ];

    return $data;
  }

}
