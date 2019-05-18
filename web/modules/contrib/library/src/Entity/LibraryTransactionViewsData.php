<?php

namespace Drupal\library\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Library transaction entities.
 */
class LibraryTransactionViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['library_transaction']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Library transaction'),
      'help' => $this->t('The Library transaction ID.'),
    ];

    return $data;
  }

}
