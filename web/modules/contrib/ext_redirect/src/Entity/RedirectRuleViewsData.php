<?php

namespace Drupal\ext_redirect\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Redirect Rule entities.
 */
class RedirectRuleViewsData extends EntityViewsData {

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
