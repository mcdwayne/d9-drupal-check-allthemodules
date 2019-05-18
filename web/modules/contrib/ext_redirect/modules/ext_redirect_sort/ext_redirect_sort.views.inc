<?php

/**
 * @file
 * Contains ext_redirect_sort\ext_redirect_sort.views.inc..
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
function ext_redirect_sort_views_data() {

  $data['views']['table']['group'] = t('Custom Global');
  $data['views']['table']['join'] = array(
    // #global is a special flag which allows a table to appear all the time.
    '#global' => array(),
  );

  $data['views']['weight_views_field'] = array(
    'title' => t('Weight views field'),
    'help' => t('Field for sorting purposes'),
    'field' => array(
      'id' => 'weight_views_field',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'help' => t('Filter by the weight value (Native handler only).'),
      'id' => 'numeric',
    ),
  );

  return $data;
}
