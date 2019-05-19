<?php

namespace Drupal\contactlist;

use Drupal\views\EntityViewsData;

class ContactListViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['contactlist_entry__groups']['groups_target_id'] = [
      'title' => $this->t('Group IDs'),
      'help' => $this->t('The raw numeric group IDs.'),
      'field' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
    ];

    return $data;
  }

}
