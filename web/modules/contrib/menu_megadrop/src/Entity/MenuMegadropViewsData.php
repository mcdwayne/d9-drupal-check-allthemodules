<?php

namespace Drupal\menu_megadrop\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Menu megadrop entities.
 */
class MenuMegadropViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
