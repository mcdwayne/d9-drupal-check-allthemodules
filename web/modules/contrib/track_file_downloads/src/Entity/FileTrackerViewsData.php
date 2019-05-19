<?php

namespace Drupal\track_file_downloads\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for File Tracker entities.
 */
class FileTrackerViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['file_tracker']['file__target_id']['relationship'] = [
      'id' => 'standard',
      'base' => 'file_managed',
      'entity type' => 'file',
      'base field' => 'fid',
      'title' => $this->t('File from File field'),
    ];
    return $data;
  }

}
