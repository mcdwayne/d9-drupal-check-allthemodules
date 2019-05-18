<?php

namespace Drupal\eloqua_app_cloud\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Eloqua AppCloud Service entities.
 */
class EloquaAppCloudServiceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['eloqua_app_cloud_service']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Eloqua AppCloud Service'),
      'help' => $this->t('The Eloqua AppCloud Service ID.'),
    );

    return $data;
  }

}
