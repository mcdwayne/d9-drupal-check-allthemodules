<?php

/**
 * @file
 */

namespace Drupal\zsm_haproxy;

use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class ZSMHAProxyPluginViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>