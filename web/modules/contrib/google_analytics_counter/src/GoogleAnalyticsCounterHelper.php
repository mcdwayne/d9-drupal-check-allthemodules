<?php

namespace Drupal\google_analytics_counter;

use Drupal\node\NodeInterface;

/**
 * Provides Google Analytics Counter helper functions.
 */
class GoogleAnalyticsCounterHelper {

  /**
   * Remove queued items from the database.
   */
  public static function gacRemoveQueuedItems() {
    $quantity = 200000;

    $connection = \Drupal::database();

    $query = $connection->select('queue', 'q');
    $query->addExpression('COUNT(*)');
    $query->condition('name', 'google_analytics_counter_worker');
    $queued_workers = $query->execute()->fetchField();
    $chunks = $queued_workers / $quantity;

    // Todo: get $t_arg working.
    $t_arg = ['@quantity' => $quantity];
    for ($x = 0; $x <= $chunks; $x++) {
      \Drupal::database()
        ->query("DELETE FROM {queue} WHERE name = 'google_analytics_counter_worker' LIMIT 200000");
    }
  }

  /**
   * Creates the gac_type_{content_type} configuration on installation or update.
   */
  public static function gacSaveTypeConfig() {
    $config_factory = \Drupal::configFactory();
    $content_types = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($content_types as $machine_name => $content_type) {
      // For updates, don't overwrite existing configuration.
      $gac_type = $config_factory->getEditable('google_analytics_counter.settings')
        ->get("general_settings.gac_type_$machine_name");
      if (empty($gac_type)) {
        $config_factory->getEditable('google_analytics_counter.settings')
          ->set("general_settings.gac_type_$machine_name", NULL)
          ->save();
      }
    }
  }

  /**
   * Get the row count of a table, sometimes with conditions.
   *
   * @param string $table
   * @return mixed
   */
  public static function getCount($table) {
    $connection = \Drupal::database();

    switch ($table) {
      case 'google_analytics_counter_storage':
        $query = $connection->select($table, 't');
        $query->addField('t', 'field_pageview_total');
        $query->condition('pageview_total', 0, '>');
        break;
      case 'google_analytics_counter_storage_all_nodes':
        $query = $connection->select('google_analytics_counter_storage', 't');
        break;
      case 'queue':
        $query = $connection->select('queue', 'q');
        $query->condition('name', 'google_analytics_counter_worker', '=');
        break;
      default:
        $query = $connection->select($table, 't');
        break;
    }
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Sets the expiry timestamp for cached queries. Default is 1 day.
   *
   * @return int
   *   The UNIX timestamp to expire the query at.
   */
  public static function cacheTime() {
    $config = \Drupal::config('google_analytics_counter.settings');
    return time() + $config->get('general_settings.cache_length');
  }

  /**
   * Search value by key in multidimensional array.
   *
   * @param array $array
   * @param $search
   *
   * @return bool|mixed
   *
   * @see https://snipplr.com/view/55684/
   */
  public static function searchArrayValueByKey(array $array, $search) {
    foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)) as $key => $value) {
      if ($search === $key)
        return $value;
    }
    return false;
  }

  /****************************************************************************/
  // Uninstall functions.
  /****************************************************************************/

  /**
   * Delete stored state values.
   */
  public static function gacDeleteState() {
    \Drupal::state()->deleteMultiple([
      'google_analytics_counter.access_token',
      'google_analytics_counter.expires_at',
      'google_analytics_counter.refresh_token',
      'google_analytics_counter.total_nodes',
      'google_analytics_counter.data_last_refreshed',
      'google_analytics_counter.profile_ids',
      'google_analytics_counter.data_step',
      'google_analytics_counter.most_recent_query',
      'google_analytics_counter.total_pageviews',
      'google_analytics_counter.total_paths',
    ]);
  }

}
