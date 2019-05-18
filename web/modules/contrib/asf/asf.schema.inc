<?php
/**
 * @file
 * The database functions and schema.
 */

/**
 *  Implements hook_schema().
 */
function asf_schema() {
  $schema['asf_schedule'] = array(
    'description' => 'Advanced scheduled nodes data.',
    'fields' => array(
      'aid' => array(
        'description' => 'The primary identifier for a action.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ),
      'nid' => array(
        'description' => 'Tidentifier for a node.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ),
      'nfid' => array(
        'description' => 'Tidentifier for a node field.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ),
      'action' => array(
        'description' => 'The workflow action that should occur',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ),
      'time' => array(
        'description' => 'When the action should occurr.',
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ),
      'status' => array(
        'description' => 'The status of the action.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'default' => 'PEN',
      ),
      'created' => array(
        'description' => 'The Unix timestamp when the schedule was created.',
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ),
      'changed' => array(
        'description' => 'The Unix timestamp when the schedule was most recently saved.',
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ),
    ),
    'indexes' => array(
      'asf_schedule_nid'         => array('nid'),
      'asf_schedule_aid'         => array('aid'),
      'asf_schedule_action_time' => array('time'),
      'asf_schedule_action_status' => array('status'),
      'asf_schedule_action' => array('action'),
    ),
    'unique keys' => array(
      'asf_id' => array('aid'),
    ),
    'primary key' => array('aid'),
  );
  return $schema;
}
