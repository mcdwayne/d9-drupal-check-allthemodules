<?php

namespace Drupal\badge\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Badge awarded entities.
 */
class BadgeAwardedViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['badge_awarded_field_data']['table']['wizard_id'] = 'badge_awared';

    return $data;
  }

}
