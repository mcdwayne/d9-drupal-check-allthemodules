<?php

namespace Drupal\dhis\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Data element entities.
 */
class DataElementViewsData extends EntityViewsData
{

    /**
     * {@inheritdoc}
     */
    public function getViewsData()
    {
        $data = parent::getViewsData();

        // Additional information for Views integration, such as table joins, can be
        // put here.

        return $data;
    }

}
