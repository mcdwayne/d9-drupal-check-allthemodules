<?php

/**
 * @file
 * Views integration and data for the redirect extensions module.
 */

/**
 * Implements hook_views_data().
 */
function redirect_extensions_views_data() {
  // The 'group' index will be used as a prefix in the UI for any of this
  // table's fields, sort criteria, etc. so it's easy to tell where they came
  // from.
  $data['redirect_extensions']['table']['group'] = t('Redirect Extensions table');

  // Define this as a base table – a table that can be described in itself by
  // views (and not just being brought in as a relationship). In reality this
  // is not very useful for this table, as it isn't really a distinct object of
  // its own, but it makes a good example.
  $data['redirect_extensions']['table']['base'] = [
    // This is the identifier field for the view.
    'field' => 'rid',
    'title' => t('Redirect Extensions Table'),
    'help' => t('Redirect Extensions table contains extended content related to redirects.'),
    'weight' => 10,
  ];

  // This table references the {redirect} table. The declaration below creates
  // an 'implicit' relationship to the redirect table, so that when 'redirect'
  // is the base table, the fields are automatically available.
  $data['redirect_extensions']['table']['join'] = [
    // Index this array by the table name to which this table refers.
    // 'left_field' is the primary key in the referenced table.
    // 'field' is the foreign key in this table.
    'redirect' => [
      'left_field' => 'rid',
      'field' => 'rid',
    ],
  ];

  // Next, describe each of the individual fields in this table to Views. This
  // is done by describing $data['redirect_extensions']['FIELD_NAME'].
  // {redirect_extensions}.rid.
  $data['redirect_extensions']['rid'] = [
    'title' => t('Redirect Extensions ID'),
    'help' => t('Some content that references a redirect.'),
  ];

  // {redirect_extensions}.uid_created.
  $data['users_field_data']['table']['join'] = [
    'redirect_extensions' => [
      'left_field' => 'users_field_data.uid',
      'field' => 'uid_created',
    ],
  ];
  $data['redirect_extensions']['uid_created'] = [
    'title' => t('Created User ID'),
    'help' => t('ID of user who created the URL redirect.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'id' => 'standard',
      'base' => 'users_field_data',
      'base field' => 'uid',
      'label' => t('created user'),
    ],
  ];

  // {redirect_extensions}.created.
  $data['redirect_extensions']['created'] = [
    'title' => t('Creation timestamp'),
    'help' => t('Just a timestamp field.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  // {redirect_extensions}.modified.
  $data['redirect_extensions']['modified'] = [
    'title' => t('Last Modified timestamp'),
    'help' => t('Just a timestamp field.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  // {redirect}.status_code.
  $data['redirect']['status_code'] = [
    'title' => t('Status code'),
    'help' => t('The redirect HTTP status code.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
