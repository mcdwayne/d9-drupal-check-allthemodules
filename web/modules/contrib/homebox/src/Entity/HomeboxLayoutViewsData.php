<?php

namespace Drupal\homebox\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Homebox Layout entities.
 */
class HomeboxLayoutViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // @todo Unused class?
    // Additional information for Views integration, such as table joins,
    // can be put here.
    return $data;
  }

}
