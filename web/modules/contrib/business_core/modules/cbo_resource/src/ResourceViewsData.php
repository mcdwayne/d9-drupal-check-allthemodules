<?php

namespace Drupal\cbo_resource;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the resource entity type.
 */
class ResourceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['resource']['users'] = [
      'title' => $this->t('Accounts'),
      'field' => [
        'id' => 'resource_users',
      ],
    ];

    return $data;
  }

}
