<?php

namespace Drupal\dcat\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Dataset entities.
 */
class DcatDatasetViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dcat_dataset']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Dataset'),
      'help' => $this->t('The Dataset ID.'),
    );

    return $data;
  }

}
