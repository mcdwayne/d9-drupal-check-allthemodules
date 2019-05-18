<?php

namespace Drupal\competition\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Competition entries.
 */
class CompetitionEntryViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['competition_entry']['table']['base'] = [
      'field' => 'ceid',
      'title' => $this->t('Competition entry'),
      'help' => $this->t('The Competition entry ID.'),
    ];

    return $data;
  }

}
