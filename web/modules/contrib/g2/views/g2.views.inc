<?php
/**
 * @file
 * Views integration
 *
 * - define g2_node as a base table
 * - allow g2_node access from node views (g2 fields in node views)
 */

/**
 * Implements hook_views_data().
 */
function g2_views_data() {

  $data['g2_node'] = array(
    'table' => array(
      'group' => t('G2'),
      'base' => array(
        'field' => 'nid',
        'title' => t('G2 Glossary'),
        'help'  => t('G2 Views are node views limited to glossary entries.'),
      ),
      'join' => array(
        'node' => array(
          'left_field' => 'nid',
          'field' => 'nid',
          // 'type' => 'INNER',
        ),
      ),
    ),
    'nid' => array(
      'field' => array(
        'handler' => 'views_handler_field_node',
        'click sortable' => TRUE,
        'title' => t('Node ID'),
        'help' => t('The ID of the glossary entry'),
      ),
    ),
    'period' => array(
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
        'title' => t('Entry life period'),
        'help' => t('This is plain text, no formatting at all.'),
      ),
    ),
    'complement' => array(
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
        'title' => t('Extra information'),
        'help' => t('This is hidden text, only displayed on the node edit form.'),
      ),
    ),
    'origin' => array(
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
        'title' => t('Origin information'),
        'help' => t('This is hidden text, only displayed on the node edit form'),
      ),
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function g2_views_data_alter(&$data) {
  $data['node']['table']['join']['g2_node'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
    'type' => 'INNER',
  );
}