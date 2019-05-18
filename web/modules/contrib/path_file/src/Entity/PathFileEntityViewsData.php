<?php

namespace Drupal\path_file\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Path file entity entities.
 */
class PathFileEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['path_file_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Path file entity'),
      'help' => $this->t('The Path file entity ID.'),
    );

    return $data;
  }

}
