<?php

namespace Drupal\badge\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Badge Name entities.
 */
class BadgeViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['badge_field_data']['table']['wizard_id'] = 'badge';

    $data['badge_field_data']['awarded']['relationship'] = array(
      'title' => $this->t('Badges Awarded'),
      'help' => $this->t('Creates the relationship with all instances awarded of the badge.'),
      'id' => 'standard',
      'base' => 'badge_awarded_field_data',
      'base field' => 'badge_id',
      'field' => 'id',
      'label' => $this->t('badges awarded'),
    );

    return $data;
  }

}
