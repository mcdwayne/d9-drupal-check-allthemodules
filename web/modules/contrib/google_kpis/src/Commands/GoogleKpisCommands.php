<?php

namespace Drupal\google_kpis\Commands;

use Drupal\google_kpis\GoogleKpisFetchAndStore;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GoogleKpisCommands extends DrushCommands {

  /**
   * Drupal\google_kpis\GoogleKpisFetchAndStore definition.
   *
   * @var \Drupal\google_kpis\GoogleKpisFetchAndStore
   */
  protected $service;

  /**
   * GoogleKpisCommands constructor.
   *
   * @param \Drupal\google_kpis\GoogleKpisFetchAndStore $service
   *   The service.
   */
  public function __construct(GoogleKpisFetchAndStore $service) {
    $this->service = $service;
  }

  /**
   * Fetch and store google statistics.
   *
   * @param array $options
   *   An associative array of options.
   *
   * @option gsc
   *   trigger command for search console api
   * @option ga
   *   trigger command for analytics api
   * @usage drush gkfs
   *   Fetch and store google statistic
   * @usage drush gkfs --gsc
   *   fetch and stores search console data only
   * @usage drush gkfs --ga
   *   fetch and stores analytics data only
   * @validate-module-enabled google_kpis
   *
   * @command google_kpis:fetchandstore
   * @aliases gkfs,google_kpis_fetch_and_store
   */
  public function googleKpisFetchAndStore(array $options = ['gsc' => NULL, 'ga' => NULL]) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details
    // on what to change when porting a legacy command.
    $service = $this->service;
    $gsc = $options['gsc'];
    $ga = $options['ga'];
    if ($gsc) {
      $search_console_data = $service->fetchGoogleSearchConsoleData();
      $service->prepareQueue($search_console_data);
      $this->logger()->success(dt('The search console API data fetched and queued successful.'));
      // Run queue.
      drush_invoke_process('@self', 'queue:run', ['google_kpis_queue']);
      return;
    }
    if ($ga) {
      $analytics_data = $service->fetchGoogleAnalyticsData();
      $service->prepareQueue($analytics_data);
      $this->logger()->success(dt('The analytics API data fetched and queued successful.'));
      // Run queue.
      drush_invoke_process('@self', 'queue:run', ['google_kpis_queue']);
      return;
    }

    $analytics_data = $service->fetchGoogleAnalyticsData();
    $search_console_data = $service->fetchGoogleSearchConsoleData();
    $data = $service->combineAnalyticsAndSearchConsoleData($analytics_data, $search_console_data);
    $service->prepareQueue($data);
    $this->logger()->success(dt('Your google data was successfully moved to the queue google_kpis_queue.'));
    // Run queue.
    drush_invoke_process('@self', 'queue:run', ['google_kpis_queue']);
  }

}
