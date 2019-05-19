<?php

/**
 * @file
 * Contains \Drupal\social_counters\SocialCountersDataViewsData.
 */
namespace Drupal\social_counters;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for social counters entities.
 */
class SocialCountersDataViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['social_counters_data']['config_label'] = array(
      'title' => t('Label of configuration'),
      'field' => array(
        'title' => t('Label of configuration'),
        'help' => t('Label of social counter configuration.'),
        'id' => 'social_counters_config_label',
      ),
    );

    return $data;
  }
}
