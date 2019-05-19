<?php

/**
 * @file
 */

namespace Drupal\zsm_system_load;

use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class ZSMSystemLoadPluginViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>