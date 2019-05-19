<?php

/**
 * @file
 */

namespace Drupal\zsm;

use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class ZSMCoreViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>