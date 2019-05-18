<?php

function accessibility_views_data() {
  // Define the base group of this table.
  $data['accessibility_test']['table']['group']  = t('Accessibility test');

  // Advertise this table as a possible base table
  $data['accessibility_test']['table']['base'] = array(
    'field' => 'test_id',
    'title' => t('Accessibility test'),
    'weight' => -10,
  );

  $data['accessibility_test']['table']['entity type'] = 'accessibility_test';
  
  $data['accessibility_test']['test_id'] = array(
    'title' => t('Test ID'),
    'help' => t('The test ID.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_test']['name'] = array(
    'title' => t('Name'),
    'help' => t('The test name.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string'
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_test']['severity'] = array(
    'title' => t('Severity'),
    'help' => t('The severity level of the test.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  
  $data['accessibility_test']['quail_name'] = array(
    'title' => t('QUAIL Name'),
    'help' => t('The name of the QUAIL test to be called.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_test']['status'] = array(
    'title' => t('Test status'),
    'help' => t('Show the status of the test.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'active-inactive' => array(t('Active'), t('Inactive')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
      // Use status = 1 instead of status <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_test']['link_accessibility'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a link to the test.'),
      'id' => 'accessibility_test_link',
    ),
  );
  
  $data['accessibility_test']['edit_accessibility'] = array(
    'title' => t('Edit Link'),
    'help' => t('Provide a link to the edit form for the accessibility.'),
    'field' => array(
      'id' => 'accessibility_test_edit',
    ),
  );
  
  $data['accessibility_test']['delete_accessibility'] = array(
    'title' => t('Delete Link'),
    'help' => t('Provide a link to delete the accessibility.'),
    'field' => array(
      'id' => 'accessibility_test_delete',
    ),
  );
    
  return $data;
}