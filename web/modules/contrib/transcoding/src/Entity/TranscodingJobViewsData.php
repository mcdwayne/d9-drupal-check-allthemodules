<?php

namespace Drupal\transcoding\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Transcoding job entities.
 */
class TranscodingJobViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['transcoding_job']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Transcoding job'),
      'help' => $this->t('The Transcoding job ID.'),
    );

    return $data;
  }

}
