<?php

namespace Drupal\rocketship\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Rocketship Feed entities.
 */
class FeedViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['rocketship_feed']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Rocketship Feed'),
      'help' => $this->t('The Rocketship Feed ID.'),
    );

    return $data;
  }

}
