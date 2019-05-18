<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Entity\Link.
 */

namespace Drupal\colossal_menu\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Link entities.
 */
class LinkViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['colossal_menu_link']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Link'),
      'help' => $this->t('The Link ID.'),
    ];

    return $data;
  }

}
