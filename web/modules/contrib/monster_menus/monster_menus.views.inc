<?php

/**
 * @file
 * Provide views data for monster_menus.module.
 */

/**
 * Implements hook_views_data().
 */
function monster_menus_views_data() {
  $data = [];
  $node_group = t('Content');

  // ----------------------------------------------------------------------
  // mm_recycle table

  $data['mm_recycle']['table']['group'] = $node_group;
  $data['mm_recycle']['table']['join'] = array(
    'node_field_data' => [
      'field' => 'id',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'extra' => [['field' => 'type', 'value' => 'node']],
    ],
    'mm_tree' => [
      'field' => 'id',
      'left_table' => 'mm_tree',
      'left_field' => 'mmtid',
      'extra' => [['field' => 'type', 'value' => 'cat']],
    ],
  );
  $data['mm_recycle']['table']['entity type'] = 'node';

  $data['mm_recycle']['recycled'] = array(
    'title' => t('Recycled'),
    'help' => t('Whether or not the node is in a recycle bin'),
    'filter' => array(
      'id' => 'is_recycled',
      'real field' => 'id',
    ),
  );

  // ----------------------------------------------------------------------
  // mm_node_schedule table

  $data['mm_node_schedule']['table']['group'] = $node_group;
  $data['mm_node_schedule']['table']['join'] = array(
    'node_field_data' => array(
      'field' => 'nid',
      'left_field' => 'nid',
    ),
  );

  $data['mm_node_schedule']['publish_on'] = array(
    'title' => t('Publish On (MM)'),
    'help' => t('When to start publishing a node'),
    'sort' => array(
      'id' => 'standard',
    ),
    'field' => array(
      'real field' => 'publish_on',
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
      'label' => t('Is Published'),
    ),
  );

  $data['mm_node_schedule']['unpublish_on'] = array(
    'title' => t('Unpublish On (MM)'),
    'help' => t('When to stop publishing a node'),
    'sort' => array(
      'id' => 'standard',
    ),
    'field' => array(
      'real field' => 'unpublish_on',
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
      'label' => t('Is Unpublished'),
    ),
  );

  // ----------------------------------------------------------------------
  // mm_node_reorder table

  $data['mm_node_reorder']['table']['group'] = $node_group;
  $data['mm_node_reorder']['table']['join'] = array(
    'node_field_data' => array(
      'field' => 'nid',
      'left_field' => 'nid',
    ),
  );

  $data['mm_node_reorder']['weight'] = array(
    'title' => t('Node Weight (MM)'),
    'help' => t('The order of this node on the MM page'),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function monster_menus_views_data_alter(&$data) {
  if (isset($data['node_field_data'])) {
    $data['node_field_data']['mm_tree'] = [
      'title' => t('Page(s) containing this content'),
      'help' => t('Page(s) on which this content appears'),
      'relationship' => [
        'label' => t('Page(s) containing this content'),
        'base' => 'mm_tree',
        'base field' => 'mmtid',
        'id' => 'sequential_join',
        'relationship_table' => 'node_field_data',
        'joins' => [
          [
            'left table' => 'node_field_data',
            'left field' => 'nid',
            'table' => 'mm_node2tree',
            'field' => 'nid',
          ],
          [
            'left field' => 'mmtid',
            'table' => 'mm_tree',
            'field' => 'mmtid',
          ],
        ],
      ],
    ];
  }

  if (isset($data['node'])) {
    $data['node']['version_history']['field'] = [
      'title' => t('Link to revision history'),
      'help' => t('Provide a link to the node\'s revision history.'),
      'id' => 'node_link_version_history',
    ];
  }
}
