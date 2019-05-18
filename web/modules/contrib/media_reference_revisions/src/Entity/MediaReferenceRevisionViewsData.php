<?php

namespace Drupal\media_reference_revisions\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Media Reference Revision entities.
 */
class MediaReferenceRevisionViewsData extends EntityViewsData {

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
