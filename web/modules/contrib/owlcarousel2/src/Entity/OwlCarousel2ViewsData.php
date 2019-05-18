<?php

namespace Drupal\owlcarousel2\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OwlCarousel2 entities.
 */
class OwlCarousel2ViewsData extends EntityViewsData {

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
