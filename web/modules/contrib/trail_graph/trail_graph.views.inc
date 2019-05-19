<?php

/**
 * @file
 * Contains trail_graph\trail_graph.views.inc..
 *
 * Provide a custom views field data that isn't tied to any other module.
 */

/**
 * Implements hook_views_data().
 */
function trail_graph_views_data() {
  $data['node']['modal_edit_form'] = [
    'title' => t('Modal edit form for Trail graph views.'),
    'help' => t('Edit link that allows editing in modal window.'),
    'field' => [
      'title' => t('Modal edit form'),
      'id' => 'modal_edit_form',
    ],
  ];
  $data['node']['modal_preview'] = [
    'title' => t('Modal node preview.'),
    'help' => t('Preview link that allows opening node in modal window.'),
    'field' => [
      'title' => t('Modal preview link'),
      'id' => 'modal_preview',
    ],
  ];
  $data['views']['trail_graph_sidebar'] = [
    'title' => t('Trail graph sidebar area'),
    'help' => t('Insert a view and form inside an area.'),
    'area' => [
      'id' => 'trail_graph_sidebar',
    ],
  ];
  return $data;
}
