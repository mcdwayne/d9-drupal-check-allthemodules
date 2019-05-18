<?php

namespace Drupal\friends\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Friends entities.
 */
class FriendsViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['friends_field_data']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Friends Request'),
      'help' => $this->t('The Friends ID.'),
    ];

    return $data;
  }

}
