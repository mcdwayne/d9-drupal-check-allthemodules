<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('agreement_type', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'name' => array(
      'type' => 'varchar',
      'length' => 100,
      'not null' => TRUE,
    ),
    'type' => array(
      'type' => 'varchar',
      'length' => 150,
      'not null' => TRUE,
    ),
    'path' => array(
      'type' => 'varchar',
      'length' => 150,
      'not null' => TRUE,
    ),
    'settings' => array(
      'type' => 'blob',
      'size' => 'big',
      'not null' => TRUE,
    ),
    'agreement' => array(
      'type' => 'text',
      'not null' => FALSE,
    ),
  ),
  'primary key' => array('id'),
  'unique keys' => array(
    'name' => array('name'),
    'path' => array('path'),
  ),
));

$connection->insert('agreement_type')
  ->fields(array('name', 'type', 'path', 'settings', 'agreement'))
  ->values(array(
    'name' => 'default',
    'type' => 'Default agreement',
    'path' => 'agreement',
    'settings' => serialize(array(
      // @todo https://www.drupal.org/project/agreement/issues/2374539
      'role' => 2,
      'title' => 'Our agreement',
      'format' => 'filtered_html',
      'frequency' => 0,
      'success' => 'Thank you for accepting our agreement.',
      'failure' => 'You must accept our agreement to continue.',
      'revoked' => 'You successfully revoked your acceptance of our agreement',
      'checkbox' => 'I agree.',
      'submit' => 'Submit',
      'destination' => '',
      'visibility_settings' => 0,
      'visibility_pages' => '',
      'email_recipient' => '',
      'reset_date' => 0,
    )),
    'agreement' => 'Default agreement.',
  ))
  ->values(array(
     'name' => 'node_1_agreement',
     'type' => 'Node 1 agreement',
     'path' => 'agree-to-node-1',
     'settings' => serialize(array(
       // @todo https://www.drupal.org/project/agreement/issues/2374539
       'role' => 3,
       'title' => 'Node 1 agreement',
       'format' => 'filtered_html',
       'frequency' => -1,
       'success' => 'Thank you for accepting our agreement.',
       'failure' => 'You must accept our agreement to continue.',
       'revoked' => 'You successfully revoked your acceptance of our agreement',
       'checkbox' => 'I agree to node 1',
       'submit' => 'Agree',
       'destination' => 'node/1',
       'visibility_settings' => 1,
       'visibility_pages' => 'node/1',
       'email_recipient' => '',
       'reset_date' => 0,
     )),
     'agreement' => 'Agree to node 1.',
  ))
  ->execute();

$connection->schema()->createTable('agreement', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'type' => array(
      'type' => 'varchar',
      'length' => 100,
      'not null' => TRUE,
      'default' => 'default',
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
  'indexes' => array(
    'type_uid' => array('type', 'uid'),
  ),
));

$connection->insert('agreement')
  ->fields(array('type', 'uid', 'agreed', 'sid', 'agreed_date'))
  ->values(array('default', 2, 1, '', 1444945097))
  ->values(array('node_1_agreement', 3, 1, '', 0))
  ->execute();
