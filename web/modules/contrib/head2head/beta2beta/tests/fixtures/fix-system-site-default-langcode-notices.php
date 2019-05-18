<?php

/**
 * @file
 * Contains SQL necessary to set `system.site.default_langcode` in order for
 * tests to pass without undefined index notice exceptions.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$config = $connection->select('config', 'c')
  ->fields('c')
  ->condition('collection', '')
  ->condition('name', 'system.site')
  ->execute()
  ->fetchAssoc();

$data = unserialize($config['data']);
if (!isset($data['default_langcode'])) {
  $data['default_langcode'] = 'en';
  $connection->update('config')
    ->fields(['data' => serialize($data)])
    ->condition('collection', '')
    ->condition('name', 'system.site')
    ->execute();
}
