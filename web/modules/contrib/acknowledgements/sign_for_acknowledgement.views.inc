<?php

/**
 * Implements hook_views_data_alter().
 *
 */
function sign_for_acknowledgement_views_data_alter(&$data) {
  if (!isset($data['sfa'])) return;
  $data['node']['sfa_status'] = array(
    'title' => t('Sfa status'),
    'group' => t('Content'),
    'field' => array(
      'title' => t('Sfa status'),
      'help' => t('Acknowledgements data status.'),
      'id' => 'sfa_status',
    ),
    'filter' => array(
      'title' => t('Signed or not'),
      'help' => t('Acknowledgements data status.'),
      'id' => 'sfa_status',
    ),
  );
}

/*
function sign_for_acknowledgement_views_pre_execute(&$view) {
  dpq($view->build_info['query']);
}
*/

/**
 * Implements hook_views_data().
 */ 
function sign_for_acknowledgement_views_data() {

  $data = array();
  $data['sfa']['table']['group'] = t('Sign for acknowledgement');
/*
  $data['sfa']['table']['base'] = array(
    'field' => 'hid', // This is the identifier field for the view.
    'table' => 'sign_for_acknowledgement',
    'title' => t('Acknowledgements table'),
    'help' => t('Acknowledgements data.'),
    'weight' => -10,
  );
*/
  $data['sfa']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'node_id',
      'table' => 'sign_for_acknowledgement',
	  /*
      'extra' => array(
        array(
          'value' => '***CURRENT_USER***',
          'numeric' => TRUE,
          'field' => 'user_id',
        ),
      ),
	  */
    ),
  );
  $data['sfa']['hid'] = array(
    'title' => t('Primary key'),
    'help' => t('primary key for acknowledgements.'),
    'table' => 'sign_for_acknowledgement',
    'field' => [
      'id' => 'numeric',
    ],
  );
  $data['sfa']['node_id'] = array(
    'title' => t('Node'),
    'table' => 'sign_for_acknowledgement',
    'help' => t('Relationship for node.'),
    'relationship' => array(
      'id' => 'standard',
      'label' => t('Node ID'),
      'base' => 'node_field_data',
      'base field' => 'nid',
    ),
  );
  $data['sfa']['user_id'] = [
    'title' => t('User'),
    'help' => t('Acknowledgement user.'),
    'table' => 'sign_for_acknowledgement',
    'field' => [
      'id' => 'sfa_user',
    ],
    'filter' => [
      'id' => 'sfa_user',
    ],
    'argument' => [
      'id' => 'sfa_user',
    ],
  ];
  $data['sfa']['mydate'] = array(
    'title' => t('Acknowledgement date'),
    'help' => t('Node signed on...'),
    'table' => 'sign_for_acknowledgement',
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}

