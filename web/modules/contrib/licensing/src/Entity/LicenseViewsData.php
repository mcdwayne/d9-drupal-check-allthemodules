<?php

namespace Drupal\licensing\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for License entities.
 */
class LicenseViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['license']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('License'),
      'help' => $this->t('The License ID.'),
    );

    return $data;
  }

}
