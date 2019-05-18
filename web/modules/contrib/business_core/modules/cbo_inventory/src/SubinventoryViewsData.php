<?php

namespace Drupal\cbo_inventory;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the subinventory entity type.
 */
class SubinventoryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['subinventory']['table']['base']['access query tag'] = 'subinventory_access';

    $data['subinventory']['bulk_form'] = [
      'title' => $this->t('Operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple subinventorys.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    return $data;
  }

}
