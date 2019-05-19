<?php

namespace Drupal\stacks\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Widget Extend entities.
 */
class WidgetExtendEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['widget_extend']['table']['base'] = [
      'field' => 'id',
      'title' => t('Widget Extend'),
      'help' => t('The Widget Extend ID.'),
    ];

    return $data;
  }

}
