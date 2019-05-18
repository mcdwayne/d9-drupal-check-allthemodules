<?php

namespace Drupal\instagram_hashtag_fetcher\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Instagram Picture Entity entities.
 */
class InstagramPictureEntityViewsData extends EntityViewsData {

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
