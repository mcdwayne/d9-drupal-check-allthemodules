<?php

/**
 * @file
 */

namespace Drupal\zsm_backup_date;

use Drupal\views\EntityViewsData;

/**
 * Provides a view to override views data for test entity types.
 */
class ZSMBackupDatePluginViewsData extends EntityViewsData {

    /**
     * {@inheritdoc}
     */
    public function getViewsData() {
        $data = parent::getViewsData();

        return $data;
    }

}
?>