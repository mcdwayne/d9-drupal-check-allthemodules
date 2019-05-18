<?php

namespace Drupal\cacheflush_ui\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Cacheflush entity entities.
 */
class CacheflushEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['cacheflush']['cacheflush_bulk_form'] = [
      'title' => t('Cacheflush operations bulk form'),
      'help' => t('Add a form element that lets you run operations on multiple entities.'),
      'field' => [
        'id' => 'cacheflush_bulk_form',
      ],
    ];

    return $data;
  }

}
