<?php

/**
 * @file
 * Contains \Drupal\cachemg\Database\CachemgDatabaseBackendFactory.
 */

namespace Drupal\cachemg\Database;

use Drupal\Core\Cache\DatabaseBackendFactory;
use Drupal\Core\Cache\DatabaseBackend;

class CachemgDatabaseBackendFactory extends DatabaseBackendFactory {

  protected $requested_page_cids;

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {

    // Process multiget cache using the default Drupal Database Backend
    // to avoid circular looping of requests.
    if ($bin == 'multiget') {
      return new DatabaseBackend($this->connection, $bin);
    }

    // Load cached data for the current page only once.
    // TODO: possibly, define a new service for that and
    // implement it in a better way.
    if (!isset($this->requested_page_cids)) {

      // TODO: Somewhy \Drupal::request() object is not
      // initialized here. Figure out why and change to \Drupal:request().
      // Possibly we should follow https://www.drupal.org/node/2237001 issue.
      global $request;
      // In some caches (for example, drush command) there are no request object.
      // For this case we have a fallback with "empty" url.
      $uri = !empty($request) ? $request->getRequestUri() : '<empty>';

      // Load array with bins and cids that were used for the current path.
      $requested_page_cids = \Drupal::cache('multiget')->get($uri);
      $this->requested_page_cids = !empty($requested_page_cids->data) ? $requested_page_cids->data : array();
    }

    // Check if there are list of requested cids for the current cache bin.
    $current_bin_cids = !empty($this->requested_page_cids['cache_' . $bin]) ? $this->requested_page_cids['cache_' . $bin] : array();

    // Initialize our cache backend wrapper.
    return new CachemgDatabaseBackend($this->connection, $bin, $current_bin_cids);
  }

  /**
   * Execute code before destroying an object.
   */
  function __destruct() {

    // Save all cache id that were requested for the current page.
    $requested_cids = &drupal_static('cachemg:requested_cids', array());

    // Get URI of the current page.
    $uri = \Drupal::request()->getRequestUri();
    $uri = !empty($uri) ? $uri : '<empty>';

    // Save information about requested cache ids to the multiget cache storage.
    \Drupal::cache('multiget')->set($uri, $requested_cids);
  }
}
