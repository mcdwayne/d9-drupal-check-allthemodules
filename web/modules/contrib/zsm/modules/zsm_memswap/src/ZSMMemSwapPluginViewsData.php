<?php

/**
 * @file
 */

namespace Drupal\zsm_memswap;

use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class ZSMMemSwapPluginViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>