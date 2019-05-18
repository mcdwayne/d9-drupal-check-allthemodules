<?php
/**
 * @file
 * Installation of tables for forena_test
 */
function forena_test_schema() {

  $schema['forena_test_data'] = array(
    'fields' => array(
      'calendar_date' => array('type' => 'date'),
      'day_of_week' => array('type' => 'varchar', 'length' => '30'),
      'day_of_month' => array('type' => 'varchar', 'length' => '30'),
      'month' => array('type' => 'varchar', 'length' => '30'),
      'year' => array('type' => 'int')
    ),
  );

  return $schema;
}