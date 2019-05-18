<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\DatabaseRawBackend.
 */

namespace Drupal\supercache\Cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

use Drupal\Core\Cache\CacheTagsChecksumInterface;

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 *
 * @ingroup cache
 */
class DatabaseRawBackend implements CacheRawBackendInterface {

  use RequestTimeTrait;

  /**
   * @var string
   */
  protected $bin;


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct(Connection $connection, $bin) {
    // All cache tables should be prefixed with 'cache_'.
    $bin = 'rawcache_' . $bin;
    $this->bin = $bin;
    $this->connection = $connection;
    $this->refreshRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids) {
    $cid_mapping = array();
    foreach ($cids as $cid) {
      $cid_mapping[$this->normalizeCid($cid)] = $cid;
    }
    // When serving cached pages, the overhead of using ::select() was found
    // to add around 30% overhead to the request. Since $this->bin is a
    // variable, this means the call to ::query() here uses a concatenated
    // string. This is highly discouraged under any other circumstances, and
    // is used here only due to the performance overhead we would incur
    // otherwise. When serving an uncached page, the overhead of using
    // ::select() is a much smaller proportion of the request.
    $result = array();
    try {
      $result = $this->connection->query('SELECT cid, data_serialized, data_string, data_int, data_float, expire, storage FROM {' . $this->connection->escapeTable($this->bin) . '} WHERE cid IN ( :cids[] ) AND (expire > :expire OR expire = :expire_permanent) ORDER BY cid',
        array(
          ':cids[]' => array_keys($cid_mapping),
          ':expire' => (int) $this->requestTime,
          ':expire_permanent' => (int) CacheRawBackendInterface::CACHE_PERMANENT,
          )
      );
    }
    catch (\Exception $e) {
      // Nothing to do.
    }
    $cache = array();
    foreach ($result as $item) {
      // Map the cache ID back to the original.
      $item->cid = $cid_mapping[$item->cid];
      $item = $this->prepareItem($item);
      if ($item) {
        $cache[$item->cid] = $item;
      }
    }
    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param object $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return mixed|false
   *   The item with data unserialized as appropriate and a property indicating
   *   whether the item is valid, or FALSE if there is no valid item to load.
   */
  protected function prepareItem($cache) {

    // Check expire time.
    $valid = $cache->expire == CacheRawBackendInterface::CACHE_PERMANENT || $cache->expire >= $this->requestTime;

    if (!$valid) {
      return FALSE;
    }

    // Retrieve the proper data...
    switch($cache->storage) {
      case 0:
        if ($cache->data_serialized === NULL) { return FALSE; }
        $cache->data = unserialize($cache->data_serialized);
        break;
      case 1:
        // Strings can actuallyl be NULL so nothing to check.
        $cache->data = $cache->data_string;
        break;
      case 2:
        if ($cache->data_int === NULL) { return FALSE; }
        $cache->data = (int) $cache->data_int;
        break;
      case 3:
        if ($cache->data_float === NULL) { return FALSE; }
        $cache->data = (float) $cache->data_float;
        break;
      default:
        throw new \Exception("Storage type  not supported. Somethign went wrong.");
    }

    // Remove storage
    unset($cache->data_serialized);
    unset($cache->data_string);
    unset($cache->data_int);
    unset($cache->data_float);
    unset($cache->storage);
    unset($cache->expire);

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    $this->setMultiple([
      $cid => [
        'data' => $data,
        'expire' => $expire,
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    $try_again = FALSE;
    try {
      // The bin might not yet exist.
      $this->doSetMultiple($items);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the bins.
      if (!$try_again = $this->ensureBinExists()) {
        // If the exception happened for other reason than the missing bin
        // table, propagate the exception.
        throw $e;
      }
    }
    // Now that the bin has been created, try again if necessary.
    if ($try_again) {
      $this->doSetMultiple($items);
    }
  }

  /**
   * Prepare data to be stored in the database.
   *
   * @param string $cid
   * @param mixed $data
   * @return array
   */
  protected function prepareStorage($cid, $data, $expire) {
    $fields = array(
        'cid' => $this->normalizeCid($cid),
        'expire' => $expire,
      );

    $fields['data_serialized'] = NULL;
    $fields['data_string'] = NULL;
    $fields['data_int'] = NULL;
    $fields['data_float'] = NULL;

    // We want to store numeric and string in a native way when this is possible.

    if (is_bool($data) || is_int($data)) {
      $fields['data_int'] = $data;
      $fields['storage'] = 2;
    }
    elseif (is_float($data)) {
      $fields['data_float'] = $data;
      $fields['storage'] = 3;
    }
    elseif (is_string($data)) {
      $fields['data_string'] = $data;
      $fields['storage'] = 1;
    }
    else {
      $fields['data_serialized'] = serialize($data);
      $fields['storage'] = 0;
    }

    return $fields;
  }

  /**
   * Stores multiple items in the persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid.
   *
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::setMultiple()
   */
  protected function doSetMultiple(array $items) {
    $values = array();

    foreach ($items as $cid => $item) {
      $item += array(
        'expire' => CacheRawBackendInterface::CACHE_PERMANENT,
      );

      $values[] = $this->prepareStorage($cid, $item['data'], $item['expire']);
    }

    // Use an upsert query which is atomic and optimized for multiple-row
    // merges.
    $query = $this->connection
      ->upsert($this->bin)
      ->key('cid')
      ->fields(array('cid', 'expire', 'data_serialized', 'data_string', 'data_int', 'data_float', 'storage'));
    foreach ($values as $fields) {
      // Only pass the values since the order of $fields matches the order of
      // the insert fields. This is a performance optimization to avoid
      // unnecessary loops within the method.
      $query->values(array_values($fields));
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->deleteMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $cids = array_values(array_map(array($this, 'normalizeCid'), $cids));
    try {
      // Delete in chunks when a large array is passed.
      // TODO: Really this should be transactional....
      foreach (array_chunk($cids, 1000) as $cids_chunk) {
        $this->connection->delete($this->bin)
          ->condition('cid', $cids_chunk, 'IN')
          ->execute();
      }
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where a cache table is cleared before it is set
      // with {cache_render} and {cache_data}.
      if (!$this->ensureBinExists()) {
        $this->catchException($e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    try {
      $this->connection->truncate($this->bin)->execute();
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where a cache table is cleared before it is set
      // with {cache_render} and {cache_data}.
      if (!$this->ensureBinExists()) {
        $this->catchException($e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    try {
      $this->connection->delete($this->bin)
        ->condition('expire', CacheRawBackendInterface::CACHE_PERMANENT, '<>')
        ->condition('expire', $this->requestTime, '<')
        ->execute();
    }
    catch (\Exception $e) {
      // If the table does not exist, it surely does not have garbage in it.
      // If the table exists, the next garbage collection will clean up.
      // There is nothing to do.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    try {
      $this->connection->schema()->dropTable($this->bin);
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0) {
    $try_again = FALSE;
    try {
      // The bin might not yet exist.
      $this->doCounter($cid, $increment, $default);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the bins.
      if (!$try_again = $this->ensureBinExists()) {
        // If the exception happened for other reason than the missing bin
        // table, propagate the exception.
        throw $e;
      }
    }
    // Now that the bin has been created, try again if necessary.
    if ($try_again) {
      $this->doCounter($cid, $increment, $default);
    }
  }

  /**
   * doCounter: if the $cid already exists
   * and is not numeric should throw an exception.
   * If it does not exist, should be populated with the
   * default value.
   * 
   * @param mixed $cid 
   * @param mixed $increment 
   * @param mixed $default 
   * @throws \Exception 
   */
  protected function doCounter($cid, $increment, $default = 0) {

    $query = $this->connection->update($this->bin);
    $query ->condition('cid', $cid);
    $query ->condition('data_int', NULL, 'IS NOT NULL');
    $query->expression('data_int', "data_int + $increment");

    $result = 0;
    try {
      $result = $query->execute();
    }
    catch (\Exception $e) { }

    if ($result == 0) {

      // Make sure the item does not exist before doing a set...
      $query = $this->connection->select($this->bin);
      $query->addField($this->bin, 'cid');
      $query->condition('cid', $this->normalizeCid($cid));
      $count = count($query->execute()->fetchAll());

      if ($count == 1) {
        throw new \Exception("Counter failed.");
      }

      // Set the default value...
      $this->counterSet($cid, $default);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0) {
    // TODO: This can be implemented in a batched way (with just one query)
    // and counter() should call counterMultiple(). But again the crippled
    // Drupal's DTBNG is brilliantly doing it's job.
    foreach ($cids as $cid) {
      $this->counter($cid, $increment, $default);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterSet($cid, $value) {
    $this->set($cid, (int) $value);
  }

  /**
   * {@inheritdoc}
   */
  public function counterSetMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->counterSet($cid, (int) $item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterGet($cid) {
    if ($result = $this->get($cid)) {
      return (int) $result->data;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function counterGetMultiple(array &$cids) {
    $results = $this->getMultiple($cids);
    $counters = [];
    foreach ($results as $cid => $item) {
      $counters[$cid] = (int) $item->data;
    }
    return $counters;
  }

  /**
   * Check if the cache bin exists and create it if not.
   */
  protected function ensureBinExists() {
    try {
      $database_schema = $this->connection->schema();
      if (!$database_schema->tableExists($this->bin)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable($this->bin, $schema_definition);
        return TRUE;
      }
    }
    // If another process has already created the cache table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when cache might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the cache is stale and the exception needs to
   * propagate.
   *
   * @param $e
   *   The exception.
   * @param string|null $table_name
   *   The table name. Defaults to $this->bin.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e, $table_name = NULL) {
    if ($this->connection->schema()->tableExists($table_name ?: $this->bin)) {
      throw $e;
    }
  }

  /**
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is at most 255 characters long.
   */
  protected function normalizeCid($cid) {
    // Nothing to do if the ID is a US ASCII string of 255 characters or less.
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
    if (strlen($cid) <= 255 && $cid_is_ascii) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    if (!$cid_is_ascii) {
      return $hash;
    }
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * Defines the schema for the {cache_*} bin tables.
   */
  public function schemaDefinition() {
    $schema = array(
      'description' => 'Storage for the cache API.',
      'fields' => array(
        'cid' => array(
          'description' => 'Primary Key: Unique cache ID.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
        ),
        'data_serialized' => array(
          'description' => 'Cache when data is serialized',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ),
        'data_string' => array(
          'description' => 'Cache data when string.',
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'big',
        ),
        'data_int' => array(
          'description' => 'Cache data when integer.',
          'type' => 'int',
          'not null' => FALSE,
          'size' => 'big',
        ),
        'data_float' => array(
          'description' => 'Cache data when float',
          'type' => 'float',
          'not null' => FALSE,
        ),
        'expire' => array(
          'description' => 'A Unix timestamp indicating when the cache entry should expire, or ' . CacheRawBackendInterface::CACHE_PERMANENT . ' for never.',
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'big',
          'default' => 0,
        ),
        'storage' => array(
          'description' => 'A flag to indicate the storage type: 0 => serialized, 1 => string, 2 => integer, 3 => float',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
        ),
      ),
      'indexes' => array(
        'expire' => array('expire'),
      ),
      'primary key' => array('cid'),
    );
    return $schema;
  }
}
