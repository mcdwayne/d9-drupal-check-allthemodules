<?php

/**
 * @file
 * Contains visualn_commerce\visualn_commerce.views.inc..
 * Provide a custom views field data that isn't tied to any other module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\Markup;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\system\ActionConfigEntityInterface;

/**
* Implements hook_views_data().
*/
function visualn_commerce_views_data() {

    // @todo: check if the handler doesn't exist in views core (see https://www.drupal.org/node/2883872)
    $data['commerce_order']['date_unixtime'] = [
        'title' => t('Created (unixtime)'),
        'help' => t('Allows to get unixtime date by pattern set.'),
        'field' => [
            'field' => 'created', // @todo: check
            'id' => 'date_unixtime',
        ],
    ];
    return $data;
}

