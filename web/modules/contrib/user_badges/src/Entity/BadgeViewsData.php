<?php

/**
 * @file
 * Contains \Drupal\user_badges\Entity\Badge.
 */

namespace Drupal\user_badges\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Badge entities.
 */
class BadgeViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['badge']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Badge'),
      'help' => $this->t('The Badge ID.'),
    );

    return $data;
  }

}
