<?php

namespace Drupal\library\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Library item entities.
 */
class LibraryItemViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['library_item']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Library item'),
      'help' => $this->t('The Library item ID.'),
    ];

    return $data;
  }

}
