<?php

namespace Drupal\contacts_events\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event entities.
 */
class EventViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $entity_type_id = $this->entityType->id();

    $data['contacts_event']['date__value']['entity_type'] = $entity_type_id;
    $data['contacts_event']['date__value']['filter']['id'] = 'datetime';
    $data['contacts_event']['date__value']['filter']['field_name'] = 'date';
    $data['contacts_event']['date__value']['sort']['id'] = 'datetime';
    $data['contacts_event']['date__value']['sort']['field_name'] = 'date';

    $data['contacts_event']['date__end_value']['entity_type'] = $entity_type_id;
    $data['contacts_event']['date__end_value']['filter']['id'] = 'datetime';
    $data['contacts_event']['date__end_value']['filter']['field_name'] = 'date';
    $data['contacts_event']['date__end_value']['sort']['id'] = 'datetime';
    $data['contacts_event']['date__end_value']['sort']['field_name'] = 'date';

    return $data;
  }

}
