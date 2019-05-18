<?php

$databases['default']['default'] = [
  'database' => 'drupal',
  'username' => 'root',
  'password' => 'drupal',
  'host' => 'mysql',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

$settings['hash_salt'] = 'whatever';

$config['drupal_inquicker']['sources']['dummy'] = [
  'source' => 'class',
  'class' => 'Drupal\drupal_inquicker\Source\DummySource',
];

if (file_exists('/unversioned-development-settings/settings.php')) {
  include '/unversioned-development-settings/settings.php';
}

$config_directories['sync'] = 'This has to exist to for drush cim --source to work correctly; it is not used';
