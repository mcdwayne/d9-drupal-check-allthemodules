<?php
/**
 * Implements hook_views_data()
 */
function proconcom_views_data() {
  $data['proconcom']['table']['group'] = t('proconcom');


  $data['proconcom']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['proconcom']['nid'] = array(
    'title' => t('proconcom: nid'),
    'help' => t('References to a node.'),
  );

  $data['proconcom']['parent_nid'] = array(
    'title' => t('Parent Node ID'),
    'help' => t('References to a parent node.'),
    'argument' => array(
      'name' => t('Post: Parent nid'),
      'handler' => 'proconcom_views_handler_argument_parent_nid',
      'help' => t('Argument for Parent nid'),
    )
  );

  $data['proconcom']['type'] = array(
    'title' => t('Argument type'),
    'help' => t('Type of the node'),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['proconcom']['status'] = array(
    'title' => t('proconcom: status'),
    'help' => t('Status of the node'),
  );

  return $data;
}

