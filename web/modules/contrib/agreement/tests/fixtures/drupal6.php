<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('agreement', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'uid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'agreed' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'sid' => array(
      'type' => 'varchar',
      'length' => 46,
    ),
    'agreed_date' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
  ),
  'primary key' => array('id'),
));

$connection->insert('agreement')
  ->fields(array('uid', 'agreed', 'sid', 'agreed_date'))
  ->values(array(2, 1, '', 1444945097))
  ->execute();
