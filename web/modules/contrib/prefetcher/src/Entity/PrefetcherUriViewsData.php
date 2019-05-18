<?php

namespace Drupal\prefetcher\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Prefetcher uri entities.
 */
class PrefetcherUriViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    $data['prefetcher_uri']['label'] = array(
      'title' => t('Label: URI/ PATH'),
      'field' => array(
        'title' => t('Label: URI/ PATH'),
        'help' => t('Displays label() of entity.'),
        'id' => 'prefetcher_label',
      ),
    );

    return $data;
  }

}
