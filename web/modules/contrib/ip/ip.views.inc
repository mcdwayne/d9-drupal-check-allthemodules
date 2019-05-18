<?php

/**
 * @file
 * Provides ip_tracker view fields and filters.
 */

/**
 * Implements hook_views_data().
 */
function ip_views_data() {
  $data = array();

  // Table: IP tracker:
  $data['ip_tracker']['table']['group'] = t('IP Tracker');
  $data['ip_tracker']['table']['base'] = array(
    'field' => 'uid',
    'title' => t('IP Tracker (user) table.'),
    'help' => t('IP Tracker (user) table.'),
    'weight' => -10,
  );

  // Uid field
  $data['ip_tracker']['uid'] = array(
    'title' => t('User id'),
    'help' => t('Unique id of user'),
    'relationship' => array(
      'base' => 'users_field_data',
      'base field' => 'uid',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('User uid'),
    ),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  // IP field
  $data['ip_tracker']['ip'] = array(
    'title' => t('IP Address'),
    'help' => t('IP address of user.'),
    'field' => array(
      'id' => 'long2ip',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'ip2long',
    ),
    'argument' => array(
      'id' => 'ip2long',
    ),
  );

  // Visit
  $data['ip_tracker']['visits'] = array(
    'title' => t('Visits'),
    'help' => t('Total visits'),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  // First visit
  $data['ip_tracker']['first_visit'] = array(
    'title' => t('First visit'),
    'help' => t('First visit: registration/login.'),
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

  // Last visit
  $data['ip_tracker']['last_visit'] = array(
    'title' => t('Last visit'),
    'help' => t('Last visit: registration/login.'),
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

  // Last visit
  $data['ip_tracker']['user_count'] = array(
    'title' => t('User count'),
    'help' => t('User count.'),
    'field' => array(
      'id' => 'ip_views_handler_field_user_count',
      'click sortable' => FALSE,
    ),
  );

  // Table: IP Posts:
  $data['ip_posts']['table']['group'] = t('IP Posts');
  $data['ip_posts']['table']['base'] = array(
    'field' => 'type',
    'title' => t('IP Posts (entity) table.'),
    'help' => t('IP Posts (entity) table.'),
    'weight' => -10,
  );
  
  // Type
  $data['ip_posts']['type'] = array(
    'title' => t('Entity type'),
    'help' => t('Entity type'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
  );

  // Id field
  $data['ip_posts']['id'] = array(
    'title' => t('Entity id'),
    'help' => t('Entity id'),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  // IP field
  $data['ip_posts']['ip'] = array(
    'title' => t('IP Address'),
    'help' => t('IP address of user.'),
    'field' => array(
      'id' => 'long2ip',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'ip2long',
    ),
    'argument' => array(
      'id' => 'ip2long',
    ),
  );

  // Node relationship: ip_posts -> node_field_data.
  $data['ip_posts']['node_field_data'] = array(
    'title' => t('Node'),
    'help' => t('Node'),
    'relationship' => array(
      'base' => 'node_field_data',
      'base field' => 'nid',
      'field' => 'id',
      'id' => 'standard',
      'label' => t('Node'),
      'extra' => array(
        0 => array(
          'left_field' => 'type',
          'value' => 'node',
        ),
      ),
    ),
  );

  // Node relationship: node_field_data -> ip_posts.
  $data['node_field_data']['ip_posts'] = array(
    'title' => t('IP Posts'),
    'help' => t('IP Posts (node)'),
    'relationship' => array(
      'base' => 'ip_posts',
      'base field' => 'id',
      'field' => 'nid',
      'id' => 'standard',
      'label' => t('IP Posts'),
      'extra' => array(
        0 => array(
          'field' => 'type',
          'value' => 'node',
        ),
      ),
    ),
  );

  // Comment relationship: ip_posts -> comment_field_data.
  $data['ip_posts']['comment_field_data'] = array(
    'title' => t('Comment'),
    'help' => t('Comment'),
    'relationship' => array(
      'base' => 'comment_field_data',
      'base field' => 'cid',
      'field' => 'id',
      'id' => 'standard',
      'label' => t('Comment'),
      'extra' => array(
        0 => array(
          'left_field' => 'type',
          'value' => 'comment',
        ),
      ),
    ),
  );

  // Comment relationship: comment_field_data -> ip_posts.
  $data['comment_field_data']['ip_posts'] = array(
    'title' => t('IP Posts'),
    'help' => t('IP Posts (comment)'),
    'relationship' => array(
      'base' => 'ip_posts',
      'base field' => 'id',
      'field' => 'cid',
      'id' => 'standard',
      'label' => t('IP Posts'),
      'extra' => array(
        0 => array(
          'field' => 'type',
          'value' => 'comment',
        ),
      ),
    ),
  );
  
  return $data;
}
