<?php

namespace Drupal\acsf\Commands;

use Drupal\acsf\AcsfSite;
use Drupal\acsf\Event\AcsfEvent;
use Drush\Commands\DrushCommands;

/**
 * Provides drush commands for site related operations.
 */
class AcsfCommands extends DrushCommands {

  /**
   * Rebuilds the ACSF registry.
   *
   * @command acsf-build-registry
   */
  public function buildRegistry() {
    acsf_build_registry();
    $this->output()->writeln(dt('The ACSF registry was rebuilt.'));
  }

  /**
   * Returns the last installation task that was completed.
   *
   * @command acsf-install-task-get
   *
   * @bootstrap root
   *
   * @return string
   *   The install task in json format.
   */
  public function installTaskGet() {
    try {
      $task = \Drupal::state()->get('install_task');
    }
    catch (\Exception $e) {
      // Do not trigger an error if the database query fails, since the database
      // might not be set up yet.
    }
    if (isset($task)) {
      return json_encode($task);
    }
  }

  /**
   * Synchronize data with the Factory.
   *
   * @command acsf-site-sync
   *
   * @bootstrap database
   *
   * @option data A base64 encoded php array describing the site generated from
   *   the Factory.
   *
   * @param array $options
   *   The command options supplied to the executed command.
   */
  public function siteSync(array $options = ['data' => NULL]) {
    $site = AcsfSite::load();
    $data = $options['data'];

    // Create an event to gather site stats to send to the Factory.
    $context = [];
    $event = AcsfEvent::create('acsf_stats', $context, $this->output());
    $event->run();
    $stats = $event->context;

    if ($data) {
      // If data was sent, we can consume it here. Ensure that we are always
      // passing associative arrays here, not objects.
      $site_info = json_decode(base64_decode($data), TRUE);
      if (!empty($site_info) && is_array($site_info)) {
        // Allow other modules to consume the data.
        $context = $site_info;
        $event = AcsfEvent::create('acsf_site_data_receive', $context, $this->output());
        $event->run();

        // For debugging purpose to be able to tell if the data has been pulled
        // or pushed.
        $site->last_sf_push = time();
        $site->saveSiteInfo($site_info['sf_site']);
      }
    }
    else {
      // If no data was sent, we'll request it.
      $site->refresh($stats);
    }
  }

  /**
   * Scrubs sensitive information regarding ACSF.
   *
   * Note that 'scrubbing' in our case doesn't mean just clearing configuration
   * values but also initializing them for use in a new website.
   *
   * drush acsf-site-scrub is called by a 'db-copy' hosting task, which in turn
   * seems to be called by the staging process.
   *
   * @command acsf-site-scrub
   */
  public function siteScrub() {
    $connection = \Drupal::database();

    // Ensure that we are testing the scrub cleanly.
    \Drupal::state()->delete('acsf_site_scrubbed');

    $this->output()->writeln(dt('Preparing to scrub users ... '));

    // Get a list of roles whose users should be preserved during staging
    // scrubbing.  Both lists are implemented as "alters" for consistency with
    // hook_acsf_duplication_scrub_preserved_users_alter.
    $preserved_roles = [];
    \Drupal::moduleHandler()->alter('acsf_staging_scrub_admin_roles', $preserved_roles);

    if (!empty($preserved_roles)) {
      $this->output()->writeln(dt('Preserving roles: @rids', ['@rids' => implode(', ', $preserved_roles)]));
    }

    // Get a list of UIDs to preserve during staging scrubbing. UIDs are first
    // obtained by the preserved roles, then can be altered to add or remove
    // UIDs.
    if (!empty($preserved_roles)) {
      $preserved_users = \Drupal::entityQuery('user')
        ->condition('roles', $preserved_roles, 'IN')
        ->execute();
    }
    else {
      $preserved_users = [];
    }
    \Drupal::moduleHandler()->alter('acsf_staging_scrub_preserved_users', $preserved_users);
    // Always preserve the anonymous user.
    $preserved_users[] = 0;
    $preserved_users = array_unique($preserved_users);

    // The anonymous user makes the size of this array always at least 1.
    $this->output()->writeln(dt('Preserving users: @uids', ['@uids' => implode(', ', $preserved_users)]));

    // Avoid collisions between the Factory and site users when scrubbing.
    $connection->update('users_field_data')
      ->expression('mail', "CONCAT('user', uid, '@', MD5(mail), '.example.com')")
      ->expression('init', "CONCAT('user', uid, '@', MD5(init), '.example.com')")
      ->condition('uid', $preserved_users, 'NOT IN')
      ->execute();

    // Reset the cron key.
    \Drupal::state()->set('system.cron_key', md5(mt_rand()));

    // Reset the drupal private key.
    \Drupal::service('private_key')->set('');

    // Reset the local site data and run acsf-site-sync to fetch factory data
    // about the site.
    $site = AcsfSite::load();
    $site->clean();
    \Drupal::service('acsf.commands')->siteSync();
    $this->logger()->success(dt('Executed acsf-site-sync to gather site data from factory and reset all acsf variables.'));

    if (\Drupal::moduleHandler()->moduleExists('acsf_sso')) {
      // Repopulate/overwrite the subset of SAML auth data which is factory /
      // sitegroup/env/factory-site-nid specific. Notes:
      // - This indeed also overwrites values which have not changed, since the
      //   site nid did not change - at least not if this is called while
      //   staging.
      //   But we want to reuse code without introducing more granularity.)
      // - We don't scrub the users' authmap data; it's fine if they retain the
      //   IDs used in live site-factory communication.
      module_load_include('install', 'acsf_sso');
      acsf_sso_install_set_env_dependent_config();
    }

    // Trigger a rebuild of router paths (formerly 'menu paths').
    \Drupal::service("router.builder")->rebuild();

    // Clear sessions and log tables that might have stale data, and whose
    // implementing classes have no dedicated 'clear()' or equivalent mechanism.
    $truncate_tables = [
      'sessions',
      'watchdog',
      'acsf_theme_notifications',
    ];
    foreach ($truncate_tables as $table) {
      if ($connection->schema()->tableExists($table)) {
        $connection->truncate($table)->execute();
      }
    }

    // Clear caches and key-value store.
    $bins = [
      'bootstrap',
      'config',
      'data',
      'default',
      'discovery',
      'dynamic_page_cache',
      'entity',
      'menu',
      'migrate',
      'render',
      'rest',
      'toolbar',
    ];
    foreach ($bins as $bin) {
      if (\Drupal::hasService("cache.$bin")) {
        \Drupal::cache($bin)->deleteAll();
      }
    }
    $bins = [
      'form',
      'form_state',
    ];
    foreach ($bins as $bin) {
      \Drupal::keyValueExpirable($bin)->deleteAll();
    }

    // Raise the database connection wait timeout (default 10 minutes) so mysql
    // will not have "gone away" after the separate sql-sanitize process ends.
    \Drupal::database()->query('SET session wait_timeout=3600');

    // Run the sql-sanitize which allows customers to use custom scrubbing. We
    // will handle email addresses and passwords ourselves.
    drush_invoke_process('@self', 'sql-sanitize', [], ['sanitize-email' => 'no', 'sanitize-password' => 'no']);

    // Mark this database as scrubbed.
    \Drupal::state()->set('acsf_site_scrubbed', 'scrubbed');
  }

}
