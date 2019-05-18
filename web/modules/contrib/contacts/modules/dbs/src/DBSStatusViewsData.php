<?php

namespace Drupal\contacts_dbs;

use Drupal\views\EntityViewsData;

/**
 * Provides the Views data for the contacts dbs entity type.
 */
class DBSStatusViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dbs_status_field_revision']['id']['relationship']['id'] = 'standard';
    $data['dbs_status_field_revision']['id']['relationship']['base'] = 'dbs_status_field_data';
    $data['dbs_status_field_revision']['id']['relationship']['base field'] = 'id';
    $data['dbs_status_field_revision']['id']['relationship']['title'] = $this->t('DBS Status');
    $data['dbs_status_field_revision']['id']['relationship']['label'] = $this->t('DBS Status');
    $data['dbs_status_field_revision']['id']['relationship']['help'] = $this->t('Get the actual status item from a revision.');

    return $data;
  }

}
