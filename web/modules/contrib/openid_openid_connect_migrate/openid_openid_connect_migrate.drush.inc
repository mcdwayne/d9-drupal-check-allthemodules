<?php

/**
 * @file
 * OpenID Connect Drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function openid_openid_connect_migrate_drush_command() {
  $items = [];
  $items['openid-openid-connect-migrate'] = [
    'aliases' => ['oocm'],
    'description' => 'Migrate users from OpenID to OpenID Connect.',
    'arguments' => [
      'client_name'    => 'The OpenID Connect client_name to import to, e.g. generic, google',
    ],
    'options' => [],
    'examples' => [
      'drush oocm' => 'Migrate all users from OpenID to OpenID Connect.',
    ],
  ];
  return $items;
}

/**
 * OpenID to OpenID Connect migration.
 */
function drush_openid_openid_connect_migrate($client_name = 'generic') {
  $plugins = openid_connect_get_plugins(TRUE);

  if (!isset($plugins[$client_name])) {
    drush_print(t('The client "@name" is not available or not enabled.', [
      '@name' => $client_name,
    ]));
    exit(1);
  }

  if (!module_exists('openid')) {
    drush_print('OpenID module is not enabled.');
    exit(1);
  }

  if (!module_exists('openid_connect')) {
    drush_print('OpenID Connect module is not enabled.');
    exit(1);
  }

  // Get all OpenID associations.
  $query = db_select('authmap', 'a')
    ->fields('a', ['uid', 'authname', 'module'])
    ->condition('module', 'openid');
  $result = $query->execute();

  $num_rows = $query->countQuery()->execute()->fetchField();
  drush_print(t('Processing @count OpenID associations...', [
    '@count' => $num_rows,
  ]));

  foreach ($result as $record) {
    // Check if an association already exists.
    $query = db_select('authmap', 'a')
      ->fields('a', ['uid', 'authname', 'module'])
      ->condition('uid', $record->uid)
      ->condition('module', 'openid_connect_' . check_plain($client_name));
    $num_rows = $query->countQuery()->execute()->fetchField();

    // If no association exists, create it.
    if (!$num_rows) {
      $account = user_load($record->uid);
      openid_connect_connect_account(
        $account,
        $client_name,
        explode('/', $record->authname)[4]
      );
      drush_print(t('Created an OpenID Connect association for user @name(@uid).', [
        '@name' => $account->name,
        '@uid' => $account->uid,
      ]));
    }
  }

}
