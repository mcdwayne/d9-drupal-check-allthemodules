<?php

namespace Drupal\couchbasedrupal\Cache;

use Drupal\couchbasedrupal\CouchbaseBucket as Bucket;
use Drupal\couchbasedrupal\CouchbaseManager;

use Couchbase\ViewQuery as CouchbaseViewQuery;

abstract class CouchbaseBackendGeneric {

  /**
   * Use N1QL and no views/documents.
   */
  const QUERYSTRATEGY_N1QL = 'QUERYSTRATEGY_N1QL';

  /**
   * Generate a pair of map/reduces for each binary.
   */
  const QUERYSTRATEGY_BINMAP = 'QUERYSTRATEGY_BINMAP';

  /**
   * Generate a single map/reduce for the whole bucket.
   */
  const QUERYSTRATEGY_GLOBALMAP = 'QUERYSTRATEGY_GLOBALMAP';

  /**
   * Generate a single map/reduce for this site prefix.
   */
  const QUERYSTRATEGY_SITEMAP = 'QUERYSTRATEGY_SITEMAP';

  /**
   * The couchbase querying strategy.
   *
   * Can be:
   *
   *  - QUERYSTRATEGY_N1QL: N1QL (use N1QL queries)
   *  - QUERYSTRATEGY_BINMAP: Map reduce per bin (use a map reduce per binary)
   *  - QUERYSTRATEGY_GLOBALMAP: Global map reduce (use a single view for the whole backend)
   *  - QUERYSTRATEGY_SITEMAP: Map reduce per site prefix.
   *
   * @var string
   */
  protected $queryStrategy = self::QUERYSTRATEGY_GLOBALMAP;

  /**
   * Prefix for all keys in this cache bin.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $binPrefix;

  /**
   * Couchbase bucket
   *
   * @var Bucket
   */
  protected $bucket;

  /**
   * Query options.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Name of the view for this binary in the Couchbase database
   *
   * @var string
   */
  protected $view;

  /**
   * Name used for the global map reduce.
   */
  const GLOBAL_MAP_REDUCE_VIEW_NAME = 'drupal_global_mr';

  /**
   * We rely on views to do bucket wide
   * operations such as getAll() and
   * removeBin(). But views take a while
   * to update (~6 seconds) depending on many
   * factors. Use TRUE to bypass the usage
   * of views and get consistent results.
   *
   * This is used during tests. Enabling consistent
   * makes bucket wide operations much slower,
   * probably due to missing indexes.
   *
   * @var bool
   */
  protected $consistent = FALSE;

  /**
   * Current time used to validate
   * cache item expiration times.
   *
   * @var mixed
   */
  protected $requestTime;

  /**
   * Refreshes the current request time.
   *
   * Uses the global REQUEST_TIME on the first
   * call and refreshes to current time on subsequen
   * requests.
   *
   * @param int $time
   */
  public function refreshRequestTime() {
    if (empty($this->requestTime)) {
      if (defined('REQUEST_TIME')) {
        $this->requestTime = REQUEST_TIME;
        return;
      }
      if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
        $this->requestTime = round($_SERVER['REQUEST_TIME_FLOAT'], 3);
        return;
      }
    }
    $this->requestTime = round(microtime(TRUE), 3);
  }

  /**
   * Set if getAll() and getAllKeys() should
   * have a reliable behaviour. Slower if set to TRUE.
   *
   * @param bool $consistent
   */
  public function setConsistent($consistent) {
    $this->consistent = $consistent;
  }

  /**
   * Prepend de binary prefix.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The storage key for the cache item ID.
   */
  protected function getBinKey($cid) {
    $key = $this->binPrefix . $cid;
    // If you get beyound 250 bytes the PHP driver
    // for couchbase hangs.
    if (mb_strlen($key, '8bit') >= 250) {
      // We need to preserve as much of the original
      // key as possible, so we are going to truncate
      // the exceeding characters and replace
      // them by a hash.
      $segment = substr($key, 0, 250 - 13);
      $key = $segment . CouchbaseManager::shortHash($key);
    }
    return $key;
  }

  /**
   * Remove any views created by this backend.
   *
   * Used when tests are finished to free
   * up the documents.
   *
   */
  public function removeViews() {
    $manager = $this->bucket->manager();
    $manager->removeDesignDocument($this->view);
  }

  /**
   * We need to setup some map reduce functions
   * for each binary so that we can perform
   * fast operations on the whole binary such as
   * getAll() or invalidateAll().
   *
   * @see getAll()
   * @see getAllKeys()
   *
   * @throws \Exception
   */
  protected function EnsureViewsGlobalMap() {

    $view_name = self::GLOBAL_MAP_REDUCE_VIEW_NAME;
    $manager = $this->bucket->manager();
    $dd = $manager->getDesignDocument($view_name);

    if (empty($dd)) {
      // NOTE: Both map reduce functions DO NOT DEPEND on the contents
      // of the document. This is to make this thing compatible
      // with both CacheBackend and RawCacheBackend.

      // To retrieve the full documents.
      $map_reduce_full = <<<EOF
   function (doc, meta) {
     emit(meta.id, doc);
   }
EOF;
      // To retrieve the keys.
      $map_reduce_key = <<<EOF
   function (doc, meta) {
     emit(meta.id, null);
   }
EOF;
      $document = array(
          'views' => array(
            // To retrieve all keys in this binary.
            'full' => ['map' => $map_reduce_full],
            'key' => ['map' => $map_reduce_key],
          )
        );

      $map = json_encode($document);

      $manager->upsertDesignDocument($view_name, $map);

      // TODO: upsertDesignDocument always returns true... even if the creation fails.
      $document_stored = $manager->getDesignDocument($view_name);
      if ($document_stored != $document) {
        throw new \Exception("Could not create view.");
      }
    }
  }

  /**
   * We need to setup some map reduce functions
   * for each binary so that we can perform
   * fast operations on the whole binary such as
   * getAll() or invalidateAll().
   *
   * @see getAll()
   * @see getAllKeys()
   *
   * @throws \Exception
   */
  protected function EnsureViewsBinMap() {

    $view_name = $this->binPrefix;
    $manager = $this->bucket->manager();
    $dd = $manager->getDesignDocument($view_name);

    if (empty($dd)) {

      $prefixLength = strlen($this->binPrefix);

      // NOTE: Both map reduce functions DO NOT DEPEND on the contents
      // of the document. This is to make this thing compatible
      // with both CacheBackend and RawCacheBackend.

      // To retrieve the full documents.
      $map_reduce_full = <<<EOF
   function (doc, meta) {
     if (meta.id.substr(0, $prefixLength) == "$this->binPrefix") {
       emit(meta.id.substr($prefixLength), doc);
     }
   }
EOF;
      // To retrieve the keys.
      $map_reduce_key = <<<EOF
   function (doc, meta) {
     if (meta.id.substr(0, $prefixLength) == "$this->binPrefix") {
       emit(meta.id.substr($prefixLength), null);
     }
   }
EOF;
      $document = array(
          'views' => array(
            // To retrieve all keys in this binary.
            'full' => ['map' => $map_reduce_full],
            'key' => ['map' => $map_reduce_key],
          )
        );

      $map = json_encode($document);

      $manager->upsertDesignDocument($view_name, $map);

      // TODO: upsertDesignDocument always returns true... even if the creation fails.
      $document_stored = $manager->getDesignDocument($view_name);
      if ($document_stored != $document) {
        throw new \Exception("Could not create view.");
      }
    }
  }


  /**
   * Returns all cached items, optionally limited by a cache ID prefix.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return string[]
   *   An array of Keys for this binary.
   */
  public function getAll($prefix = '') {
    switch ($this->queryStrategy) {
      case self::QUERYSTRATEGY_BINMAP:
        return $this->getAllBinMap($prefix);
      case self::QUERYSTRATEGY_N1QL:
        return $this->getAllN1ql($prefix);
      case self::QUERYSTRATEGY_GLOBALMAP:
        return $this->getAllGlobalMap($prefix);
      default:
        throw new \Exception("Not implemented.");
    }
  }

  /**
   * Get all the keys for the items
   * in this binary.
   *
   * @return array
   */
  public function getAllKeys($prefix = '') {
    switch ($this->queryStrategy) {
      case self::QUERYSTRATEGY_BINMAP:
        return $this->getAllKeysBinMap($prefix);
      case self::QUERYSTRATEGY_N1QL:
        return $this->getAllKeysN1ql($prefix);
      case self::QUERYSTRATEGY_GLOBALMAP:
        return $this->getAllKeysGlobalMap($prefix);
      default:
        throw new \Exception("Not implemented.");
    }
  }

  /**
   * Implementation that uses a map reduce view.
   *
   * @param mixed $prefix
   * @return array
   */
  private function getAllBinMap($prefix = '') {
    // Search in the full map reduce.
    $query = CouchbaseViewQuery::from($this->view, 'full');
    // Make sure we use an updated index.
    if ($this->consistent) {
      $query->stale(CouchbaseViewQuery::UPDATE_BEFORE);
    }

    // Select by a given prefix.
    if (!empty($prefix)) {
      $query->range($prefix, $prefix . "\uefff", TRUE);
    }

    //->custom(array('startkey' => "%22$prefix%22"));
    // https://forums.couchbase.com/t/upgrade-php-couchbase-2-1-0/5885/3
    try {
      $result = (array) $this->bucket->queryView($query);
    }
    catch (\Couchbase\Exception $e) {
      // There is a chance that the view might not exist yet...
      // This thing has a 0 code :(
      if ($e->getMessage() == 'case_clause: {not_found,deleted}'
        || strpos($e->getMessage(), 'not_found:') === 0) {
        $this->EnsureViewsBinMap(TRUE);
        $result = (array) $this->bucket->queryView($query);
      }
    }
    return array_column($result['rows'], 'value');
  }

  private function getAllGlobalMap($prefix = '') {
    // Add the binary prefix.
    $prefix = $this->getBinKey('') . $prefix;

    // Search in the full map reduce.
    $query = CouchbaseViewQuery::from(self::GLOBAL_MAP_REDUCE_VIEW_NAME, 'full');

    // Make sure we use an updated index.
    if ($this->consistent) {
      $query->stale(CouchbaseViewQuery::UPDATE_BEFORE);
    }

    // Select by a given prefix.
    if (!empty($prefix)) {
      $query->range($prefix, $prefix . "\uefff", TRUE);
    }

    $result = [];

    //->custom(array('startkey' => "%22$prefix%22"));
    // https://forums.couchbase.com/t/upgrade-php-couchbase-2-1-0/5885/3
    try {
      $result = (array) $this->bucket->queryView($query);
    }
    catch (CouchbaseException $e) {
      // There is a chance that the view might not exist yet...
      // This thing has a 0 code :(
      if ($e->getMessage() == 'case_clause: {not_found,deleted}'
        || strpos($e->getMessage(), 'not_found:') === 0) {
        $this->EnsureViewsGlobalMap(TRUE);
        $result = (array) $this->bucket->queryView($query);
      }
    }
    return array_column($result['rows'], 'value');
  }

  /**
   * Implementation that uses N1QL
   *
   * @param mixed $prefix
   * @return array
   */
  private function getAllN1ql($prefix = '') {
    $bin_key_length = strlen($this->getBinKey(''));
    $result = $this->bucket->getAllItemsByPrefix($this->getBinKey('') . $prefix);
    $cache = array();
    foreach ($result as $cid => $item) {
      if (!empty($item->error) && $item->error->code == 13) {
        continue;
      }
      $item = $this->prepareItem($item, FALSE);
      if ($item) {
        $cache[substr($cid, $bin_key_length)] = $item;
      }
    }
    unset($result);
    return $cache;
  }

  /**
   * Implementation using a map reduce.
   *
   * @return array
   */
  private function getAllKeysBinMap() {
    // Search in the keys map reduce.
    $query = CouchbaseViewQuery::from($this->view, 'key');

    // Make sure we use an updated index.
    if ($this->consistent) {
      $query->stale(CouchbaseViewQuery::UPDATE_BEFORE);
    }

    // Select by a given prefix.
    if (!empty($prefix)) {
      $query->range($prefix, $prefix . "\uefff", TRUE);
    }

    $result = [];

    // https://forums.couchbase.com/t/upgrade-php-couchbase-2-1-0/5885/3
    try {
      $result = (array) $this->bucket->queryView($query);
    }
    catch (CouchbaseException $e) {
      // There is a chance that the view might not exist yet...
      // This thing has a 0 code :(
      if ($e->getMessage() == 'case_clause: {not_found,deleted}'
        || strpos($e->getMessage(), 'not_found:') === 0) {
        $this->EnsureViewsBinMap(TRUE);
        $result = (array) $this->bucket->queryView($query);
      }
      else {
        throw $e;
      }
    }

    return array_column($result['rows'], 'key');
  }

  /**
   * Implementation using a map reduce.
   *
   * @return array
   */
  private function getAllKeysGlobalMap($prefix = '') {
    $prefix = $this->getBinKey('') . $prefix;

    // Search in the keys map reduce.
    $query = CouchbaseViewQuery::from(self::GLOBAL_MAP_REDUCE_VIEW_NAME, 'key');

    // Make sure we use an updated index.
    if ($this->consistent) {
      $query->stale(CouchbaseViewQuery::UPDATE_BEFORE);
    }

    // Select by a given prefix.
    if (!empty($prefix)) {
      $query->range($prefix, $prefix . "\uefff", TRUE);
    }

    $result = [];

    // https://forums.couchbase.com/t/upgrade-php-couchbase-2-1-0/5885/3
    try {
      $result = (array) $this->bucket->queryView($query);
    }
    catch (CouchbaseException $e) {
      // There is a chance that the view might not exist yet...
      // This thing has a 0 code :(
      if ($e->getMessage() == 'case_clause: {not_found,deleted}'
        || strpos($e->getMessage(), 'not_found:') === 0) {
        $this->EnsureViewsGlobalMap(TRUE);
        $result = (array) $this->bucket->queryView($query);
      }
      else {
        throw $e;
      }
    }
    $keys = array_column($result['rows'], 'key');

    // In the global map the key is the same thing as the
    // global Id... so we need to remove that information.
    $keys = preg_replace("/^{$this->binPrefix}/", '', $keys);

    return $keys;
  }

  /**
   * Implementation using N1QL
   *
   * @return array
   */
  private function getAllKeysN1ql() {
    $result = $this->bucket->getAllKeysByPrefix($this->getBinKey(''));
    return array_values($result);
  }

}
