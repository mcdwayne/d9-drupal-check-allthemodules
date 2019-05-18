<?php

namespace Drupal\contacts_events\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ticket entities.
 */
class TicketViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['contacts_ticket']['status']['filter']['id'] = 'list_field';
    $data['contacts_ticket']['status']['filter']['field_name'] = 'status';

    return $data;
  }

}
