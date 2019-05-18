<?php

namespace Drupal\couchbasedrupal;

use Couchbase\BucketManager as CouchbaseBucketManager;
use Couchbase\Cluster as CouchbaseCluster;
use Couchbase\N1qlQuery as CouchbaseN1qlQuery;
use Couchbase\ViewQuery as CouchbaseViewQuery;

/**
 * We need some extra magic around
 * the bucket.
 */
class CouchbaseBucket {

  /**
   * Couchbase bucket
   *
   * @var \Couchbase\Bucket
   */
  protected $bucket;

  /**
   * Bucket name.
   *
   * @var string
   */
  protected $name;

  /**
   * Bucket password.
   *
   * @var string
   */
  protected $password;

  /**
   * If N1ql client is enabled.
   *
   * @var bool
   */
  protected $N1qlEnabled = FALSE;

  /**
   * If a bucket operation has failed
   * in resilient retry logic (most probably server is down).
   *
   * @var bool
   */
  protected $failed;

  /**
   * Runs a couchbase operation with retry logic.
   *
   * @param callable $callable
   */
  protected function executeCouchbaseOperation($callable) {
    // Number of failures.
    $failures = 0;
    // Maximum retry time (s).
    $max_downtime = 6;
    // Operation start time.
    $start = microtime(TRUE);
    while (TRUE) {
      try {
        return call_user_func($callable);
      } catch (\Exception $e) {
        if ($this->failed) {
          throw $e;
        }
        // If we have already failed, or this is not a
        // transient error code rethrow.
        if (!in_array((string) $e->getCode(), CouchbaseExceptionCodes::getTransientErrors())) {
          throw $e;
        }
        $failures++;
        // Limit the number of retries.
        if ((microtime(TRUE) - $start) > $max_downtime) {
          $this->failed = TRUE;
          throw $e;
        }
        // Wait time proportional
        // to the number of failures
        usleep($failures * (0.5 * 1000000));
      }
    }
  }

  /**
   * Fix expiration for better precision.
   *
   * @see http://docs.couchbase.com/developer/dev-guide-3.0/doc-expiration.html
   */
  public function FixExpiration(int $expire) : int {
    // Because time() might not be exactly the same in PHP and in Couchbase,
    // use reltive TTL() instead of absolute for small timeouts.
    $timeout = $expire - time();
    if ($timeout < 30 * 24 * 60 * 60) {
      $result = (int) $timeout;
    }
    else {
      $result = (int) $expire;
    }

    return $result > 0 ? $result : 1;
  }

  /**
   * Get an instance of CouchbaseBucket
   *
   * @param CouchbaseCluster $cluster
   * @param mixed $name
   * @param TranscoderInterface $transcoder
   */
  public function __construct(CouchbaseCluster $cluster, $name, $password = NULL, TranscoderInterface $transcoder = NULL) {
    $args = [$name];
    if (!empty($password)) {
      $args[] = $password;
    }
    $this->executeCouchbaseOperation(function () use ($cluster, $args) {
      $this->bucket = call_user_func_array([$cluster, 'openBucket'], $args);
    });
    // If the user provides a transcoder, use it.
    if ($transcoder) {
      $this->bucket->setTranscoder([$transcoder, 'encode'], [
        $transcoder,
        'decode'
      ]);
    }
    $this->name = $name;
    $this->password = $password;
    $this->enableN1ql();
  }

  /**
   * Retrieve an item from the bucket. Returns
   * FALSE if it does not exist.
   *
   * @param string $key
   * @return \Couchbase\Document
   */
  public function get(string $key) {
    try {
      // We can delete all of them at once!
      return $this->executeCouchbaseOperation(
        function () use ($key) {
          return $this->bucket->get($key);
        }
      );
    } catch (\Couchbase\Exception $e) {
      if ((string) $e->getCode() == CouchbaseExceptionCodes::KEY_DOES_NOT_EXIST) {
        return FALSE;
      }
      throw $e;
    }
  }

  /**
   * Retrieve a list of items from the bucket.
   *
   * @param string[] $key
   *  An array of keys for documents to retrieve.
   * @return \Couchbase\Document[]
   */
  public function getMultiple(array $keys) {
    return $this->executeCouchbaseOperation(
      function () use ($keys) {
        return $this->bucket->get($keys);
      }
    );
  }

  /**
   * Remove an item from the bucket without
   * throwing and exception if it does not exist.
   *
   * @param string $keys
   *   A key or an array of keys to remove.
   * @return bool
   *   Always returns TRUE.
   */
  public function remove($key) {
    try {
      // We can delete all of them at once!
      $this->executeCouchbaseOperation(
        function () use ($key) {
          $this->bucket->remove($key);
        }
      );
    } catch (\Exception $e) {
      if ((string) $e->getCode() == CouchbaseExceptionCodes::KEY_DOES_NOT_EXIST) {
        return FALSE;
      }
      throw $e;
    }
    return TRUE;
  }

  /**
   * Remove an item from the bucket without
   * throwing and exceptino if it does not exist.
   *
   * @param string[] $keys
   *   An array of keys to remove.
   * @return bool
   *   Always returns TRUE.
   */
  public function removeMultiple($keys) {
    // We can delete all of them at once!
    $this->executeCouchbaseOperation(
      function () use ($keys) {
        return $this->bucket->remove($keys);
      }
    );
    return TRUE;
  }

  /**
   * Upsert an intem in the bucket.
   *
   * @param string $id
   * @param mixed $val
   * @param array $options
   */
  public function upsert($id, $val = NULL, $options = []) {
    return $this->executeCouchbaseOperation(
      function () use ($id, $val, $options) {
        return $this->bucket->upsert($id, $val, $options);
      }
    );
  }

  /**
   * Insert an item in a bucket. If the item
   * already exists returns FALSE.
   *
   * @param string $id
   * @param mixed $val
   * @param mixed $options
   * @return bool
   */
  public function insert($id, $val = NULL, $options = []) {
    try {
      $this->executeCouchbaseOperation(
        function () use ($id, $val, $options) {
          $this->bucket->insert($id, $val, $options);
        }
      );
      return TRUE;
    } catch (\Exception $e) {
      // 12,13 are document already exists or document does not exist.
      if (in_array((string) $e->getCode(), [
        CouchbaseExceptionCodes::KEY_DOES_NOT_EXIST,
        CouchbaseExceptionCodes::KEY_ALREADY_EXISTS
      ])) {
        return FALSE;
      }
      throw $e;
    }
  }

  /**
   * Summary of touch
   *
   * @param string $id
   * @param int $expiry
   * @param array $options
   * @return mixed
   */
  public function touch($id, $expiry, array $options = []) {
    return $this->executeCouchbaseOperation(
      function () use ($id, $expiry, $options) {
        return $this->bucket->touch($id, $expiry, $options);
      }
    );
  }

  /**
   * Summary of counter
   *
   * @param mixed $ids
   * @param mixed $ids
   * @param array $options
   * @return mixed
   */
  public function counter($ids, $delta, array $options = []) {
    return $this->executeCouchbaseOperation(
      function () use ($ids, $delta, $options) {
        return $this->bucket->counter($ids, $delta, $options);
      }
    );
  }

  /**
   * Get the bucket manager.
   *
   * @return CouchbaseBucketManager
   */
  public function manager() {
    return $this->bucket->manager();
  }

  /**
   * Escapes prefixes for cache strings identifiers.
   *
   * @param string $prefix
   *   Cache string prefix to escape.
   *
   * @return string
   *   Escaped prefix.
   */
  public function escapePrefix($prefix) {
    $prefix = addcslashes($prefix, "%_\\\"");
    return addslashes($prefix);
  }

  /**
   * Delete elements by a prefix.
   *
   * @param string $prefix
   *   Prefix of documents to delete.
   *
   * @return bool
   *   Boolean indicating if deletion was succesful.
   */
  public function deleteAllByPrefix(string $prefix) {
    $prefix = $this->escapePrefix($prefix);

    return $this->executeCouchbaseOperation(
      function () use ($prefix) {
        $query = "DELETE FROM {$this->name} WHERE META({$this->name}).id LIKE \"{$prefix}%\"";
        $query = CouchbaseN1qlQuery::fromString($query);

        /** @var \Drupal\couchbasedrupal\CouchbaseManager */
        $couchbase_manager = \Drupal::service('couchbasedrupal.manager');

        if (($info = $this->manager()->info())
          && isset($info['name'])
          && isset($couchbase_manager->settings['servers'][$info['name']]['max_parallelism'])
        ) {
          $query->max_parallelism = $couchbase_manager->settings['servers'][$info['name']]['max_parallelism'];
        }

        $result = $this->queryN1QL($query);
        return ($result->status ?? NULL) === 'success';
      }
    );
  }

  /**
   * Get the name of the bucket
   *
   * @return string
   */
  public function getName() : string {
    return $this->name;
  }

  /**
   * Return all keys that start with a prefix
   * using N1QL.
   *
   * @param string $prefix
   * @return mixed
   */
  public function getAllKeysByPrefix($prefix) {
    $prefix = $this->escapePrefix($prefix);
    $prefix_length = strlen($prefix);

    $query = "SELECT SUBSTR(META({$this->name}).id, $prefix_length) AS cid, META({$this->name}).id as id FROM {$this->name} WHERE META({$this->name}).id LIKE \"{$prefix}%\"";
    $result = (array) $this->queryN1QL(CouchbaseN1qlQuery::fromString($query));
    return array_combine(array_column($result, 'id'), array_column($result, 'cid'));
  }

  /**
   * Return all keys that start with a prefix
   * using N1QL.
   *
   * Implementation should not be based on document contents
   * because it is shared between Backend and RawBackend.
   *
   * @param string $prefix
   * @return mixed
   */
  public function getAllItemsByPrefix($prefix) {

    // Due to limitations in N1QL the most easy implementation
    // right now is to retrieve the keys and then the elements
    // because N1QL can only retrieve full documents and not
    // binary values.
    $keys = $this->getAllKeysByPrefix($prefix);
    return $this->getMultiple(array_keys($keys));
  }

  /**
   * Enables client side N1QL
   */
  public function enableN1ql() {
    // Looks like enableN1ql got removed in newer versions of the PHP  extension
    if (!$this->N1qlEnabled && method_exists($this->bucket, 'enableN1ql')) {
      $this->bucket->enableN1ql([
        'http://1.1.1.1:8093/',
        'http://1.1.1.2:8093/'
      ]);
      $this->N1qlEnabled = TRUE;
    }
  }

  /**
   * Add acces credentials to a N1QL query.
   *
   * https://forums.couchbase.com/t/how-to-execute-n1ql-queries-against-a-sasl-password-protected-bucket/7752
   * SASL auth buckets need the creds option to run N1QL queries.
   *
   * @param CouchbaseN1qlQuery $query
   */
  protected function addCredentialsToQuery(CouchbaseN1qlQuery &$query) {
    if (!empty($this->password)) {
      $query->options["creds"] = [
        ["user" => "local:{$this->name}", "pass" => $this->password]
      ];
    }
  }

  /**
   * Run a N1QL query
   *
   * @param CouchbaseN1qlQuery $query
   * @param mixed $params
   * @param mixed $json_array
   *
   * @return mixed
   */
  public function queryN1QL(CouchbaseN1qlQuery $query, $json_array = FALSE) {
    try {
      $this->addCredentialsToQuery($query);
      return $this->executeCouchbaseOperation(
        function () use ($query, $json_array) {
          return $this->bucket->query($query, $json_array);
        }
      );
    } catch (\Exception $e) {
      // CREATE INDEX idx ON mybucket(`key`, `value` IS VALUED) WHERE `value` IS VALUED;
      // https://forums.couchbase.com/t/explanation-on-primary-scan-secondary-scan/6048/12
      $code = isset($e->qCode) ? $e->qCode : $e->getCode();
      if ($code == 4000) {
        try {
          $fixquery = CouchbaseN1qlQuery::fromString("CREATE PRIMARY INDEX ON {$this->name} USING GSI");
          $this->addCredentialsToQuery($fixquery);
          $this->bucket->query($fixquery, $json_array);
        } catch (\Exception $e2) {
          // Do nothing...
        }
        return $this->bucket->query($query, $json_array);
      }
      throw $e;
    }
  }

  /**
   * Run a view query
   *
   * @param CouchbaseN1qlQuery $query
   * @param mixed $params
   * @param mixed $json_array
   * @return mixed
   */
  public function queryView(CouchbaseViewQuery $query, bool $json_array = FALSE) {
    return $this->bucket->query($query, $json_array);
  }

  /**
   * Clear all the documents (views) in this
   * bucket.
   */
  public function clearAllDocuments() {
    $manager = $this->manager();
    $documents = $manager->getDesignDocuments();
    foreach ($documents as $name => $definition) {
      $manager->removeDesignDocument($name);
    }
  }

  /**
   * Get information about an index, or false
   * if it does not exist.
   *
   * @param string $name
   *   Name of the index.
   *
   * @return array|FALSE
   */
  public function indexGet(string $name) {
    $query = CouchbaseN1qlQuery::fromString("SELECT * FROM system:indexes WHERE name='{$name}' AND keyspace_id='{$this->name}'");
    $result = (array) $this->queryN1QL($query);
    return $result['rows'][0]->indexes ?? FALSE;
  }

  /**
   * Drop an index.
   *
   * @param string $name
   *   Index name.
   */
  public function indexDrop(string $name) {
    $query = CouchbaseN1qlQuery::fromString("DROP INDEX $name");
    $this->queryN1QL($query);
  }

  /**
   * Check if an index exists.
   *
   * @param string $name
   *   The name of the index.
   *
   * @return bool
   */
  public function indexExists(string $name) : bool {
    return $this->indexGet($name) !== FALSE;
  }
}
