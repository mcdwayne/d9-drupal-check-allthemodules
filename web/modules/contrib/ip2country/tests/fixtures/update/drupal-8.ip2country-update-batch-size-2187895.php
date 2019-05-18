<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz.
 *
 * This fixture enables the ip2country module by setting system configuration
 * values in the {config} and {key_value} tables, then adds the Ip2Country
 * version 8.x-1.8 ip2country.settings configuration to the {config} table.
 *
 * This fixture is intended for use in testing ip2country_update_8101().
 *
 * @see https://www.drupal.org/node/2187895.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the ip2country DB schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'ip2country',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension to enable ip2country.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['ip2country'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Install the Ip2Country configuration. This is from ip2country-8.x-1.8.
$configs[] = [
  'watchdog' => TRUE,
  'rir' => 'arin',
  'md5_checksum' => FALSE,
  'update_interval' => 604800,
  'debug' => FALSE,
  'test_type' => 0,
  'test_country' => '',
  'test_ip_address' => '',
];

/*
 * Another way to install the configuration is to store it as a.yml file in
 * tests/fixtures/update then read it in here. For example:
 * Import YAML config files for Ip2Country.
 * @code
 * $configs[] = \Drupal\Component\Serialization\Yaml::decode(
 *   file_get_contents(__DIR__ . '/ip2country-8.x-1.8.settings.yml')
 * );
 * @endcode
 *
 * Or, you can create a pre-serialized string of the above configuration array
 * and embed it below in the 'data' field. For example:
 * @code
 *    'data' => 'a:8:{s:8:"watchdog";b:1;s:3:"rir";s:4:"arin";s:12:"md5_checksum";b:0;s:15:"update_interval";i:604800;s:5:"debug";b:0;s:9:"test_type";i:0;s:12:"test_country";s:0:"";s:15:"test_ip_address";s:0:"";}',
 * @endcode
 */

// Save configuration(s) in the database.
foreach ($configs as $config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'ip2country.settings',
      'data' => serialize($config),
    ])
    ->execute();
}
