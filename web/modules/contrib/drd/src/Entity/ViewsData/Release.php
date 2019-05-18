<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Release entities.
 */
class Release extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_release']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('DRD Release'),
      'help' => $this->t('DRD Release ID.'),
    ];

    $data['drd_release']['status_label']['field'] = [
      'title' => $this->t('Update Status Label'),
      'help' => $this->t('TBD'),
      'id' => 'drd_update_status',
    ];

    $data['drd_release']['updatestatus']['filter']['id'] = 'drd_update_status';

    $data['drd_release']['domains']['relationship'] = [
      'title' => $this->t('Domains using this release'),
      'label' => $this->t('Domains using this release'),
      'help' => $this->t('TBD.'),
      'id' => 'entity_reverse',
      'base' => 'drd_domain',
      'entity_type' => 'drd_domain',
      'base field' => 'id',
      'field_name' => 'releases',
      'field table' => 'drd_domain__releases',
      'field field' => 'releases_target_id',
    ];

    return $data;
  }

}
