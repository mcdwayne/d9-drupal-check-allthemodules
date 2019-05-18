<?php

namespace Drupal\cbo_resource;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the resource_list entity type.
 */
class ResourceListViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['resource_list']['bulk_form'] = [
      'title' => $this->t('Operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple resource lists.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    return $data;
  }

}
