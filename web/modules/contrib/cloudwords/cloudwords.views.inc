<?php

/**
 * @file
 * Cloudwords views integration.
 */


// @todo move handler definitions to EntityViewsData classes
/**
 * Implements hook_views_data_alter().
 */
function cloudwords_views_data_alter(array &$info) {
  $info['cloudwords_translatable']['add_to_project_checkbox'] = [
    'title' => t('Add to project checkbox'),
    'field' => [
      'title' => t('Add to project'),
      'help' => t('Add to project'),
      'id' => 'add_to_project_checkbox',
    ],
  ];

  $info['cloudwords_translatable']['label']['field']['handler'] = 'cloudwords_views_handler_label';

  $info['cloudwords_translatable']['content_type'] = [
    'title' => t('node'),
    'help' => t('The content type (for example, "blog entry", "forum post", "story", etc).'),
    'real field' => 'objectid',
    'relationship' => [
      'title' => t('Content type'),
      'help' => t('Relate a translatable to its content type.'),
      'handler' => 'cloudwords_handler_relationship',
      'base' => 'node',
      'base field' => 'nid',
      'field' => 'objectid',
      'label' => t('content type'),
      'filter key' => 'type',
      'filter value' => 'node_content',
      'filter remove' => 'type_1',
    ],
    'filter' => [
      'handler' => 'views_handler_filter_node_type',
    ],
  ];

  $info['cloudwords_translatable']['vocabulary'] = [
    'title' => t('Vocabulary'),
    'help' => t('The vocabulary for this taxonomy term.'),
    'real field' => 'objectid',
    'relationship' => [
      'title' => t('Vocabulary'),
      'help' => t('Relate a translatable to its vocabulary.'),
      'handler' => 'cloudwords_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'field' => 'objectid',
      'label' => t('Vocabulary'),
      'filter key' => 'type',
      'filter value' => 'taxonomy_term',
      'filter remove' => 'vocabulary',
    ],
    'filter' => [
      'handler' => 'views_handler_filter_vocabulary_vid',
    ],
  ];

  $info['cloudwords_translatable']['uid']['filter']['handler'] = 'cloudwords_handler_filter_project_user';

  $info['cloudwords_translatable']['in_translation'] = [
    'help' => t('Whether this project is currently in translation.'),
    'title' => t('In translation'),
    'real field' => 'status',
    'filter' => [
      'handler' => 'views_handler_filter_numeric',
    ],
  ];
}


/**
 * Implements hook_views_data().
 */
function cloudwords_views_data() {
  // @todo limit these to only cloudwords views
  $data['views']['cloudwords_create_project_button_area'] = [
    'title' => t('Cloudwords - Create project button'),
    'help' => t(''),
    'area' => [
      'id' => 'cloudwords_create_project_button_area',
    ],
  ];

  $data['locales_source']['table'] = [
    'group' => t('Locales Source'),
    'join' => [
      'cloudwords_translatable' => [
        'left_field' => 'objectid',
        'field' => 'lid',
      ],
    ],
  ];


  $data['locales_source']['source'] = [
    'title' => t('Source'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  return $data;
}