<?php

namespace Drupal\flipping_book\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Flipping Book entities.
 */
class FlippingBookViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['flipping_book']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Flipping Book'),
      'help' => $this->t('The Flipping Book ID.'),
    );

    return $data;
  }

}
