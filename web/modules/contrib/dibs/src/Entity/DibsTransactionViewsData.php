<?php

namespace Drupal\dibs\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Dibs transaction entities.
 */
class DibsTransactionViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dibs_transaction']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Dibs transaction'),
      'help' => $this->t('The Dibs transaction ID.'),
    );

    return $data;
  }

}
