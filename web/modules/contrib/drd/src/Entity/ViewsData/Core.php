<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Core entities.
 */
class Core extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_core']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('DRD Core'),
      'help' => $this->t('DRD Core ID.'),
    ];

    $data['drd_core']['status_agg']['field'] = [
      'title' => $this->t('DRD Core Status'),
      'help' => $this->t('Show the aggregated status of all domains of this core with colored icons'),
      'id' => 'drd_core_status_agg',
    ];
    $data['drd_core']['drd_entity_actions']['field'] = [
      'title' => $this->t('Actions'),
      'help' => $this->t('Add a form element that lets you run operations on multiple cores.'),
      'id' => 'drd_entity_actions',
    ];

    $data['drd_core']['id']['filter']['id'] = 'drd_cores';

    return $data;
  }

}
