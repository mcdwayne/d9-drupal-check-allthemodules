<?php

namespace Drupal\invite\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Invite entities.
 */
class InviteViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['invite']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Invite'),
      'help' => $this->t('The Invite ID.'),
    ];

    return $data;
  }

}
