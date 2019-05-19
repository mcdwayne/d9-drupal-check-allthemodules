<?php

/**
 * @file
 */

namespace Drupal\spectra\Entity\Views;

use Drupal\views\EntityViewsData;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a view to override views data for test entity types.
 */
class SpectraDataViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>