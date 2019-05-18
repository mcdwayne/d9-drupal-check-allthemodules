<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Project entities.
 */
class Project extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_project']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('DRD Project'),
      'help' => $this->t('DRD Project ID.'),
    ];

    $data['drd_project']['label']['field']['id'] = 'drd_project_name';

    $data['drd_project']['project_types']['filter'] = [
      'title' => t('Project types'),
      'field' => 'type',
      'id' => 'drd_project_types',
    ];

    $data['drd_project']['majors']['relationship'] = [
      'title' => $this->t('Majors of this project'),
      'label' => $this->t('Majors of this project'),
      'help' => $this->t('TBD.'),
      'id' => 'standard',
      'base' => 'drd_major',
      'base field' => 'project',
      'field' => 'id',
    ];

    return $data;
  }

}
