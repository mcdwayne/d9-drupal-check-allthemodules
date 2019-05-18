<?php

namespace Drupal\flashpoint_community_content\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Flashpoint community content entities.
 */
class FlashpointCommunityContentViewsData extends EntityViewsData {

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
