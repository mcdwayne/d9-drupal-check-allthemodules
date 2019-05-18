<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Host entities.
 */
class Host extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_host']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('DRD Host'),
      'help' => $this->t('DRD Host ID.'),
    ];

    $data['drd_host']['status_agg']['field'] = [
      'title' => $this->t('DRD Host status'),
      'help' => $this->t('Show the aggregated status of all domains of all cores on this host with colored icons'),
      'id' => 'drd_host_status_agg',
    ];
    $data['drd_host']['drd_entity_actions']['field'] = [
      'title' => $this->t('Actions'),
      'help' => $this->t('Add a form element that lets you run operations on multiple hosts.'),
      'id' => 'drd_entity_actions',
    ];

    $data['drd_host']['id']['filter']['id'] = 'drd_hosts';

    return $data;
  }

}
