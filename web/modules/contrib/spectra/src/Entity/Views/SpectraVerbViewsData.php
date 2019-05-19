<?php

namespace Drupal\spectra\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Spectra verb entities.
 */
class SpectraVerbViewsData extends EntityViewsData {

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
