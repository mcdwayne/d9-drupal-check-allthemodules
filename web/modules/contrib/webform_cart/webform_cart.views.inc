<?php

/**
 * @file
 * Contains webform_cart\webform_cart.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */

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
function webform_cart_views_data() {

    $data['views']['table']['group'] = t('Custom Global');
    $data['views']['table']['join'] = [
      // #global is a special flag which allows a table to appear all the time.
      '#global' => [],
    ];


    $data['views']['add_to_cart'] = [
        'title' => t('Add to cart'),
        'help' => t('Provides form elements for choosing quantity and adding to cart.'),
        'field' => [
            'id' => 'add_to_cart',
        ],
    ];

    return $data;
}
