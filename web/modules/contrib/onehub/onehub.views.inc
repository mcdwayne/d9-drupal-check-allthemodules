<?php

/**
 * @file
 * Provide views data for onehub.module.
 */

/**
 * Implements hook_views_data().
 */
function onehub_views_data() {

  $data['onehub']['table']['group'] = t('OneHub');
  $data['onehub']['table']['base'] = [
    'field' => 'oid',
    'title' => t('OneHub Files'),
  ];

  $data['onehub']['oid'] = [
    'title' => t('OneHub File ID'),
    'help' => t('The OneHub File ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['oid'] = [
    'title' => t('OneHub Downloadable Filename'),
    'help' => t('Downloadable button for the field.'),
    'field' => [
      'id' => 'onehub_views_filename',
      'help' => t('Downloadable button for the field.'),
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['filename'] = [
    'title' => t('OneHub Filename'),
    'help' => t('The OneHub Filename.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['entity_id'] = [
    'title' => t('Base Entity ID'),
    'help' => t('The Entity ID the OneHub file is on.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['workspace'] = [
    'title' => t('OneHub Workspace ID'),
    'help' => t('The OneHub Workspace ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['workspace_name'] = [
    'title' => t('OneHub Workspace Name'),
    'help' => t('The OneHub Workspace Name'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['folder'] = [
    'title' => t('OneHub Folder ID'),
    'help' => t('The OneHub Folder ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['folder_name'] = [
    'title' => t('OneHub Folder Name'),
    'help' => t('The OneHub Folder Name.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['onehub']['timestamp'] = [
    'title' => t('OneHub File Uploaded'),
    'help' => t('Time of the OneHub File upload.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  return $data;
}