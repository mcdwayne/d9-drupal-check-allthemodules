<?php

/**
 * @file
 * Drush command file for Drush 8.
 */

use Drupal\drd\Plugin\Action\BaseGlobalInterface;
use Drupal\drd\Plugin\Action\BaseInterface;
use Drupal\drd\Plugin\Action\Base;
use Drupal\user\Entity\User;

/**
 * Implements hook_drush_command().
 */
function drd_drush_command() {
  $select_options = [
    'tag' => [
      'description' => t('Tag name'),
    ],
    'host-id' => [
      'description' => t('Host ID'),
    ],
    'host' => [
      'description' => t('Host name'),
    ],
    'core-id' => [
      'description' => t('Core ID'),
    ],
    'core' => [
      'description' => t('Core name'),
    ],
    'domain-id' => [
      'description' => t('Domain ID'),
    ],
    'domain' => [
      'description' => t('Domain'),
    ],
  ];
  $host_options = [] + $select_options;
  $core_options = [] + $select_options;
  $domain_options = [] + $select_options;

  $items = [
    'drd-blocks' => [
      'options' => $domain_options + [
        'module' => ['description' => t('The remote module from which to receive blocks')],
        'delta' => ['description' => t('The identifier of a specific block within a module')],
      ],
    ],
    'drd-cron' => [
      'options' => $domain_options,
    ],
    'drd-database' => [
      'options' => $domain_options,
    ],
    'drd-dnslookup' => [
      'options' => $host_options,
    ],
    'drd-domainchange' => [
      'options' => $domain_options + [
        'newdomain' => ['description' => t('The new domain name to be used.')],
        'secure' => ['description' => t('When changing from https to http or the other way round, use 0 (for http) or 1 (for https).')],
        'port' => ['description' => t('Provide the port number if it is not default.')],
        'force' => ['description' => t('Provide this option to save the domain entity regardless of the ping status.')],
      ],
    ],
    'drd-domainmove' => [
      'arguments' => [
        'dest-core-id' => ['description' => t('The core id to which the domains should be moved.')],
      ],
      'options' => $domain_options,
    ],
    'drd-domains-enableall' => [
      'options' => $core_options,
    ],
    'drd-domains-receive' => [
      'options' => $core_options,
    ],
    'drd-download' => [
      'arguments' => [
        'source' => ['description' => t('The remote filename to download.')],
        'destination' => ['description' => t('The local filename where to store the downloaded file.')],
      ],
      'options' => $domain_options,
    ],
    'drd-errorlogs' => [
      'options' => $domain_options,
    ],
    'drd-flushcache' => [
      'options' => $domain_options,
    ],
    'drd-info' => [
      'options' => $domain_options,
    ],
    'drd-jobscheduler' => [
      'options' => $domain_options,
    ],
    'drd-l10n-update' => [
      'options' => $domain_options,
    ],
    'drd-library-push' => [
      'arguments' => [
        'source' => ['description' => t('Possible values are official|local to select from where the library should be pushed to domains.')],
      ],
      'options' => $domain_options,
    ],
    'drd-library-build' => [],
    'drd-maintenancemode' => [
      'arguments' => [
        'mode' => ['description' => t('Possible values are getStatus|on|off.')],
      ],
      'options' => $domain_options,
    ],
    'drd-php' => [
      'arguments' => [
        'php' => ['description' => t('Arbitrary PHP code to be executed remotely.')],
      ],
      'options' => $domain_options,
    ],
    'drd-ping' => [
      'options' => $domain_options + [
        'save' => ['description' => t('With this option, the ping status will be saved to the domain entity.')],
      ],
    ],
    'drd-projects-usage' => [
      'options' => $domain_options,
    ],
    'drd-projects-status' => [],
    'drd-projects-update' => [
      'options' => $core_options + [
        'dry-run' => ['description' => t('Perform the update in the working directory but do not commit, push or deploy')],
        'show-log' => ['description' => t('Show the log output')],
        'list' => ['description' => t('Output a list of available updates')],
        'include-locked' => ['description' => t('Also include locked releases')],
        'security-only' => ['description' => t('Only include security updates')],
        'force-locked-security' => ['description' => t('Always include security updates, even if locked')],
      ],
    ],
    'drd-projects-update-log' => [
      'options' => $core_options + [
        'id' => ['description' => t('Shows the latest by default, you can get any of the other from the list with this option')],
        'list' => ['description' => t('List die available logs')],
      ],
    ],
    'drd-session' => [
      'options' => $domain_options,
    ],
    'drd-update' => [
      'options' => $domain_options,
    ],
    'drd-user-credentials' => [
      'arguments' => [
        'uid' => ['description' => t('The user ID for which own or some of the following credentials should be changed.')],
      ],
      'options' => $domain_options + [
        'username' => ['description' => t('The user name to be set')],
        'password' => ['description' => t('The password to be set')],
        'status' => ['description' => t('The status to be set')],
      ],
    ],

    'drd-cleanup-unused-releases' => [],
    'drd-cleanup-unused-majors' => [],
    'drd-cleanup-unused-projects' => [],
    'drd-reset-all-projects-data' => [],
  ];

  return $items;
}

/**
 * Configure and return the Entities service.
 *
 * @return \Drupal\drd\Entities
 *   Configured DRD Entities service.
 */
function _drd_drush_get_service() {
  drush_set_error('Drush 8 support in DRD is deprecated, please upgrade to Drush 9.');
  return \Drupal::service('drd.entities')
    ->setTag(drush_get_option('tag'))
    ->setHost(drush_get_option('host'))
    ->setHostId(drush_get_option('host-id'))
    ->setCore(drush_get_option('core'))
    ->setCoreId(drush_get_option('core-id'))
    ->setDomain(drush_get_option('domain'))
    ->setDomainId(drush_get_option('domain-id'));
}

/**
 * Get the DRD Cleanup service.
 *
 * @return \Drupal\drd\Cleanup
 *   DRD Cleanup service.
 */
function _drd_drush_get_cleanup_service() {
  drush_set_error('Drush 8 support in DRD is deprecated, please upgrade to Drush 9.');
  return \Drupal::service('drd.cleanup');
}

/**
 * Callback to read one or more domains.
 *
 * @return bool|\Drupal\Core\Entity\EntityInterface[]
 *   List of DrdDomains.
 */
function _drd_drush_get_domains() {
  return _drd_drush_get_service()->domains();
}

/**
 * Callback to read one or more cores.
 *
 * @return bool|\Drupal\Core\Entity\EntityInterface[]
 *   List of DrdCores.
 */
function _drd_drush_get_cores() {
  return _drd_drush_get_service()->cores();
}

/**
 * Callback to read one or more hosts.
 *
 * @return bool|\Drupal\Core\Entity\EntityInterface[]
 *   List of DrdHosts.
 */
function _drd_drush_get_hosts() {
  return _drd_drush_get_service()->hosts();
}

/**
 * Load and return the DRD Action for the given id.
 *
 * @param string $action_key
 *   The id of the action to be loaded.
 *
 * @return bool|\Drupal\drd\Plugin\Action\BaseEntityInterface
 *   Returns the loaded DRD Action if available or FALSE otherwise.
 */
function _drd_drush_get_action($action_key) {
  $action = Base::instance($action_key);
  if (!$action || !($action instanceof BaseInterface)) {
    drush_set_error('No valid action!');
    return FALSE;
  }
  \Drupal::currentUser()->setAccount(User::load(1));
  return $action;
}

/**
 * Callback to execute an action on one or many drd entities.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   List of DRD entities on which to execute the action.
 * @param string $action_key
 *   The id of the action which should be executed.
 * @param array $arguments
 *   List of required parameters for the action.
 * @param array $options
 *   List of optional parameters for the action.
 * @param array $defaults
 *   Default values for the optional parameters.
 */
function _drd_drush_action(array $entities, $action_key, array $arguments = [], array $options = [], array $defaults = []) {
  if (!$entities) {
    return;
  }

  $action = _drd_drush_get_action($action_key);
  if (!$action) {
    return;
  }

  foreach ($arguments as $key => $value) {
    $action->setActionArgument($key, $value);
  }

  foreach ($options as $key) {
    $value = isset($defaults[$key]) ? drush_get_option($key, $defaults[$key]) : drush_get_option($key);
    if (isset($value)) {
      $action->setActionArgument($key, $value);
    }
  }

  /* @noinspection PhpDeprecationInspection */
  drush_print('Executing ' . $action->getPluginDefinition()['label']);
  foreach ($entities as $entity) {
    /* @var \Drupal\drd\Entity\BaseInterface $entity */
    /* @noinspection PhpDeprecationInspection */
    drush_print('- on id ' . $entity->id() . ': ' . $entity->getName());
    if ($action->executeAction($entity) !== FALSE) {
      /* @noinspection PhpDeprecationInspection */
      drush_print('  ok!');
    }
    else {
      drush_set_error('  failure!');
    }

    $output = $action->getOutput();
    if ($output) {
      foreach ($output as $value) {
        /* @noinspection PhpDeprecationInspection */
        drush_print('  ' . $value);
      }
    }
  }

  _drd_drush_finalize();

  /* @noinspection PhpDeprecationInspection */
  drush_print('Completed!');
}

/**
 * Callback to execute a system action.
 *
 * @param string $action_key
 *   The id of the DRD system action to be executed.
 */
function _drd_drush_action_system($action_key) {
  $action = _drd_drush_get_action($action_key);

  if (empty($action) || !($action instanceof BaseGlobalInterface)) {
    drush_set_error('No valid local action!');
    return;
  }

  /* @noinspection PhpDeprecationInspection */
  drush_print('Executing ' . $action->getPluginDefinition()['label']);
  if ($action->executeAction()) {
    /* @noinspection PhpDeprecationInspection */
    drush_print('  ok!');
  }
  else {
    drush_set_error('  failure!');
  }

  _drd_drush_finalize();

  /* @noinspection PhpDeprecationInspection */
  drush_print('Completed!');
}

/**
 * Finalize the Drush command execution.
 */
function _drd_drush_finalize() {
  /* @var \Drupal\drd\QueueManager $q */
  $q = \Drupal::service('queue.drd');
  $q->processAll();
}

/**
 * Callback to execute drd-blocks Drush command.
 */
function drush_drd_blocks() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_blocks', [], ['module', 'delta']);
}

/**
 * Callback to execute drd-cron Drush command.
 */
function drush_drd_cron() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_cron');
}

/**
 * Callback to execute drd-database Drush command.
 */
function drush_drd_database() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_database');
}

/**
 * Callback to execute drd-dnslookup Drush command.
 */
function drush_drd_dnslookup() {
  _drd_drush_action(_drd_drush_get_hosts(), 'drd_action_dnslookup');
}

/**
 * Callback to execute drd-domainchange Drush command.
 */
function drush_drd_domainchange() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_domainchange', [], [
    'newdomain',
    'secure',
    'port',
    'force',
  ], ['force' => FALSE]);
}

/**
 * Callback to execute drd-domainmove Drush command.
 *
 * @param int $dest_core_id
 *   The core id to which the domains should be moved.
 */
function drush_drd_domainmove($dest_core_id) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_domainmove', [
    'dest-core-id' => $dest_core_id,
  ]);
}

/**
 * Callback to execute drd-domains-enableall Drush command.
 */
function drush_drd_domains_enableall() {
  _drd_drush_action(_drd_drush_get_cores(), 'drd_action_domains_enableall');
}

/**
 * Callback to execute drd-domains-receive Drush command.
 */
function drush_drd_domains_receive() {
  _drd_drush_action(_drd_drush_get_cores(), 'drd_action_domains_receive');
}

/**
 * Callback to execute drd-download Drush command.
 *
 * @param string $source
 *   The remote filename to download.
 * @param string $destination
 *   The local filename where to store the downloaded file.
 */
function drush_drd_download($source, $destination) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_download', [
    'source' => $source,
    'destination' => $destination,
  ]);
}

/**
 * Callback to execute drd-errorlogs Drush command.
 */
function drush_drd_errorlogs() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_error_logs');
}

/**
 * Callback to execute drd-flushcache Drush command.
 */
function drush_drd_flushcache() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_flush_cache');
}

/**
 * Callback to execute drd-info Drush command.
 */
function drush_drd_info() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_info');
}

/**
 * Callback to execute drd-jobscheduler Drush command.
 */
function drush_drd_jobscheduler() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_job_scheduler');
}

/**
 * Callback to execute drd-l10n-update Drush command.
 */
function drush_drd_l10n_update() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_update_translations');
}

/**
 * Callback to execute drd-library-push Drush command.
 *
 * @param string $source
 *   Possible values are official|local to select from where the library should
 *   be pushed to domains.
 */
function drush_drd_library_push($source) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_library', [
    'source' => $source,
  ]);
}

/**
 * Callback to execute drd-library-build Drush command.
 */
function drush_drd_library_build() {
  _drd_drush_action_system('drd_action_library_build');
}

/**
 * Callback to execute drd-maintenancemode Drush command.
 *
 * @param string $mode
 *   Possible values are getStatus|on|off.
 */
function drush_drd_maintenancemode($mode) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_maintenance_mode', [
    'mode' => $mode,
  ]);
}

/**
 * Callback to execute drd-php Drush command.
 *
 * @param string $php
 *   Arbitrary PHP code to be executed remotely.
 */
function drush_drd_php($php) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_php', [
    'php' => $php,
  ]);
}

/**
 * Callback to execute drd-ping Drush command.
 */
function drush_drd_ping() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_ping', [], ['save'], ['save' => FALSE]);
}

/**
 * Callback to execute drd-projects-usage Drush command.
 */
function drush_drd_projects_usage() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_projects');
}

/**
 * Callback to execute drd-projects-status Drush command.
 */
function drush_drd_projects_status() {
  _drd_drush_action_system('drd_action_projects_status');
}

/**
 * Callback to execute drd-projects-update Drush command.
 */
function drush_drd_projects_update() {
  _drd_drush_action(_drd_drush_get_cores(), 'drd_action_projects_update', [], ['dry-run', 'show-log', 'list', 'include-locked', 'security-only', 'force-locked-security']);
}

/**
 * Callback to show logs from drd-projects-update Drush command.
 */
function drush_drd_projects_update_log() {
  _drd_drush_action(_drd_drush_get_cores(), 'drd_action_projects_update_log', [], ['id', 'list']);
}

/**
 * Callback to execute drd-session Drush command.
 */
function drush_drd_session() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_session');
}

/**
 * Callback to execute drd-update Drush command.
 */
function drush_drd_update() {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_update');
}

/**
 * Callback to execute drd-user-credentials Drush command.
 *
 * @param int $uid
 *   The user ID for which own or some of the following credentials should be
 *   changed.
 */
function drush_drd_user_credentials($uid) {
  _drd_drush_action(_drd_drush_get_domains(), 'drd_action_user_credentials', [
    'uid' => $uid,
  ], ['username', 'password', 'status']);
}

/**
 * Callback to execute drd-cleanup-unused-releases Drush command.
 */
function drush_drd_cleanup_unused_releases() {
  _drd_drush_get_cleanup_service()->cleanupReleases();
}

/**
 * Callback to execute drd-cleanup-unused-majors Drush command.
 */
function drush_drd_cleanup_unused_majors() {
  _drd_drush_get_cleanup_service()->cleanupMajors();
}

/**
 * Callback to execute drd-cleanup-unused-projects Drush command.
 */
function drush_drd_cleanup_unused_projects() {
  _drd_drush_get_cleanup_service()->cleanupProjects();
}

/**
 * Callback to execute drd-reset-all-projects-data Drush command.
 */
function drush_drd_reset_all_projects_data() {
  _drd_drush_get_cleanup_service()->resetAll();
}
