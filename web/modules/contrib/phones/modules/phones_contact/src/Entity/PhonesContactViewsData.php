<?php

namespace Drupal\phones_contact\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Phones contact entities.
 */
class PhonesContactViewsData extends EntityViewsData {

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
