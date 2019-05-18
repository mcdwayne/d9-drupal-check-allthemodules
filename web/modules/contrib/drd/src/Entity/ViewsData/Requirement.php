<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Requirement entities.
 */
class Requirement extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_requirement']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Requirement'),
      'help' => $this->t('The Requirement ID.'),
    ];

    $data['drd_requirement']['warning_domains']['relationship'] = [
      'title' => $this->t('Domains with this warning'),
      'label' => $this->t('Domains with this warning'),
      'help' => $this->t('TBD.'),
      'id' => 'entity_reverse',
      'base' => 'drd_domain',
      'entity_type' => 'drd_domain',
      'base field' => 'id',
      'field_name' => 'warnings',
      'field table' => 'drd_domain__warnings',
      'field field' => 'warnings_target_id',
    ];
    $data['drd_requirement']['error_domains']['relationship'] = [
      'title' => $this->t('Domains with this error'),
      'label' => $this->t('Domains with this error'),
      'help' => $this->t('TBD.'),
      'id' => 'entity_reverse',
      'base' => 'drd_domain',
      'entity_type' => 'drd_domain',
      'base field' => 'id',
      'field_name' => 'errors',
      'field table' => 'drd_domain__errors',
      'field field' => 'errors_target_id',
    ];

    $data['drd_requirement']['category']['field']['id'] = 'drd_requirement_category';

    return $data;
  }

}
