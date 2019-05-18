<?php

/**
 * @file
 * Contains block_panelizer_usage\block_panelizer_usage.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */

/**
 * Implements hook_views_data_alter().
 */
function block_panelizer_usage_views_data_alter(array &$data) {

  $data['block_content']['block_panelizer_usage__block_regions'] = [
    'title' => t('Panelizer Usage: Block by Region Report'),
    'help' => t('A report of the block regions in which blocks appear.'),
    'field' => [
      'id' => 'block_panelizer_usage__block_regions',
    ],
  ];

  $data['block_content']['block_panelizer_usage__default_view_modes'] = [
    'title' => t('Panelizer Usage: Blocks in Panelizered Display Modes Report'),
    'help' => t('A report of the panelizered display modes in which blocks appear.'),
    'field' => [
      'id' => 'block_panelizer_usage__default_view_modes',
    ],
  ];

  $data['block_content']['block_panelizer_usage__panelizered_nodes'] = [
    'title' => t('Panelizer Usage: Blocks in Panelizered Nodes Report'),
    'help' => t('A report of the panelizered nodes on which blocks appear.'),
    'field' => [
      'id' => 'block_panelizer_usage__panelizered_nodes',
    ],
  ];
}
