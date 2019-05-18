<?php
/**
 * @file
 * Views module integration hooks.
 */

/**
 * Implements hook_views_data().
 */
function most_viewed_views_data() {
  $data = [];
  $data['most_viewed_hits']['table']['group'] = t('MVC entities');

  $data['most_viewed_hits']['table']['base'] = [
    'field' => 'id',
    'title' => t('Most Viewed Content'),
    'help' => t('The primary identifier for a MVC hit.'),
  ];

  $data['most_viewed_hits']['table']['join'] = [
    'node' => [
      'left_field' => 'entity_id',
      'field' => 'nid',
    ],
  ];

  $data['most_viewed_hits']['id'] = [
    'title' => t('MVC ID'),
    'help' => t('The MVC primary identifier.'),
    'field' => [
      'id' => 'numeric',
//      'handler' => 'views_handler_field_numeric',
//      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
//      'handler' => 'views_handler_filter_numeric',
    ],
    'sort' => [
      'id' => 'numeric',
//      'handler' => 'views_handler_sort',
    ],
  ];

  $data['most_viewed_hits']['entity_id'] = [
    'title' => t('Entity id'),
    'help' => t('The entity identifier.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'numeric',
    ],
  ];

  $data['most_viewed_hits']['entity_type'] = [
    'title' => t('Entity type'),
    'help' => t('The entity type.'),
    'field' => [
      'id' => 'standard',
//      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'standard',
//      'handler' => 'views_handler_filter_string',
    ],
    'sort' => [
      'id' => 'standard',
//      'handler' => 'views_handler_sort',
    ],
  ];

  $data['most_viewed_hits']['bundle'] = [
    'title' => t('Bundle'),
    'help' => t('The bundle.'),
    'field' => [
      'handler' => 'views_handler_field',
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'standard',
      'handler' => 'views_handler_filter_string',
    ],
    'sort' => [
      'id' => 'standard',
      'handler' => 'views_handler_sort',
    ],
  ];

  $data['most_viewed_hits']['created'] = [
    'title' => t('Date'),
    'help' => t('The Unix timestamp when the hit was created.'),
    'field' => [
//      'handler' => 'views_handler_field_date',
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
//      'handler' => 'views_handler_filter_date',
      'id' => 'date',
    ],
    'sort' => [
//      'handler' => 'views_handler_sort_date',
      'id' => 'date',
    ],
  ];

//  $data['node']['most_viewed_sort'] = [
//    'title' => t('Most viewed sort'),
//    'help' => t('Vost viewed sort'),
//    'sort' => [
//      'handler' => 'most_viewed_handler_sort_most_viewed',
//    ],
//  ];

  return $data;
}
