<?php

namespace Drupal\quick_code;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the quick_code entity type.
 */
class QuickCodeViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['quick_code']['type']['argument']['id'] = 'quick_code_type';

    $data['quick_code']['bulk_form'] = [
      'title' => $this->t('Operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple entities.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    $data['quick_code']['effective'] = [
      'title' => $this->t('Effective'),
      'help' => $this->t('Filter the view to the effective code.'),
      'filter' => [
        'id' => 'quick_code_effective',
      ],
    ];

    $data['quick_code']['quick_code_filter'] = [
      'title' => $this->t('Quick code filter'),
      'help' => $this->t('Provides a quick code list to filter quick codes.'),
      'area' => [
        'id' => 'quick_code_filter',
      ],
    ];

    return $data;
  }

}
