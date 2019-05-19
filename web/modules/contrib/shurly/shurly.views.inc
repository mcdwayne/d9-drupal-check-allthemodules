<?php

/**
 * @file
 * Shurly Views data include file
 */

function shurly_views_data() {

  $data = array();

  $data['shurly'] = array();

  $data['users'] = array();

  $data['users_data'] = array();

  $data['users_field_data'] = array();

  $data['shurly']['table']['group']  = t('Shurly');

  $data['shurly']['table']['provider'] = 'shurly';

  // not sure if this is the best way, but it allows to get access to the user fields data
  $data['shurly']['table']['entity type'] = 'user';

  $data['shurly']['table']['base'] = array(
    'field' => 'rid',
    'title' => t('Short URLs'),
    'help' => t('Shurly shortened URLs.'),
    'weight' => 10,
  );

  $data['users']['table']['join']['shurly'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );

  $data['users_data']['table']['join']['shurly'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );

  $data['users_field_data']['table']['join']['shurly'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );

  // User ID
  $data['shurly']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('ID of user who created the link.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'name field' => 'name',
    ),
    'filter' => array(
      'title' => t('Name'),
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('user'),
    ),
  );

  // uid
  $data['shurly']['uid_current'] = array(
    'real field' => 'uid',
    'title' => t('Current user'),
    'help' => t('Filter the view to the currently logged in user.'),
    'filter' => array(
      'id' => 'user_current',
      'type' => 'yes-no',
    ),
  );

  // Shurly Source - Short URL
  $data['shurly']['source'] = array(
    'title' => t('Short URL'),
    'help' => t('The source URL which generates a redirect'),
    'field' => array(
      'id' => 'shurly_short_url',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Shurly Desination - Long URL
  $data['shurly']['destination'] = array(
    'title' => t('Long URL'),
    'help' => t('The destination URL'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Link created timestamp
  $data['shurly']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date/time the link was created.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

  // Count
  $data['shurly']['count'] = array(
    'title' => t('Clicks'),
    'help' => t('Number of times this link has been followed.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),

  );

  // Link last used timestamp
  $data['shurly']['last_used'] = array(
    'title' => t('Last used date'),
    'help' => t('The last date/time the link was used.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

  // Boolean for custom entry
  $data['shurly']['custom'] = array(
    'title' => t('Custom URL'),
    'help' => t('Boolean flag set for custom short URLs entered by user.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Custom'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'boolean',
    ),
  );

  // Boolean for active link
  $data['shurly']['active'] = array(
    'title' => t('URL active'),
    'help' => t('Is this link active or has it been deactivated?'),
    'field' => array(
      'id' => 'boolean',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Custom'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'boolean',
    ),
  );

  $data['shurly']['link_edit'] = [
    'title' => t('Edit URL'),
    'help' => t('Link to edit the URL'),
    'field' => [
      'id' => 'shurly_link_edit'
    ],
  ];

  $data['shurly']['link_deactivate'] = [
    'title' => t('Deactivate URL'),
    'help' => t('Link to deactivate the URL'),
    'field' => [
      'id' => 'shurly_link_deactivate'
    ],
  ];

  return $data;
}
