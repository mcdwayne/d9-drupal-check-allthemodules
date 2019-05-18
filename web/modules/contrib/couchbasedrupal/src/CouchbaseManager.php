<?php

namespace Drupal\couchbasedrupal;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Psr7\Uri;

use Drupal\couchbasedrupal\CouchbaseBucket as Bucket;
use Couchbase\Cluster as CouchbaseCluster;

/**
 * This is a service used to manage the connections
 * to the different couchbase servers.
 */
class CouchbaseManager {

  /**
   * Generates a shortened sha256 hash. While there is always the risk of
   * collision (when different $data generate the same hash), shortened hashes
   * increase that risk. This function should only be used when that additional
   * risk is acceptable.
   *
   * @param string $data
   *   Message to be hashed.
   * @param string $key
   *   Shared secret key.
   * @param int $length
   *   Hash length.
   *
   * @return string
   *   A sha256 base 36 encoded shortened hash.
   */
  public static function shortHash($data, $key = NULL, $length = 10) {
    if ($key) {
      return substr(base_convert(hash_hmac('sha256', $data, $key), 16, 36), 0, $length);
    }
    else {
      return substr(base_convert(hash('sha256', $data), 16, 36), 0, $length);
    }
  }

  /**
   * Couchbase server settings.
   *
   * @var array
   */
  var $settings;

  /**
   * Prefix for this site.
   *
   * @var string
   */
  protected $site_prefix;

  /**
   * Get the prefix to use for this site.
   *
   * @return string
   */
  public function getSitePrefix() {
    return $this->site_prefix;
  }

  /**
   * Settings
   *
   * @param Settings $settings
   */
  public function __construct(Settings $settings, $root, $site_path) {
    // Add a default for a local couchbase server.
    $this->settings = ['servers' =>
      ['default' => ['uri' => 'couchbase://127.0.0.1'] ]
    ];
    // Merge or override with custom settings.
    $this->settings = array_merge($this->settings, $settings::get('couchbasedrupal', []));
    $this->site_prefix = self::shortHash(Settings::getApcuPrefix('apc_backend', $root, $site_path));
  }

  /**
   * List of instanced clusters.
   *
   * @var CouchbaseCluster[]
   */
  protected $clusters = [];

  /**
   * This is static to ensure bucket connections are reused.
   *
   * @var Bucket[]
   */
  protected $buckets = [];


  /**
   * List of transcoders
   *
   * @var TranscoderInterface[]
   */
  protected $transcoders = [];

  /**
   * Gets the bucket name to use for a specific cluster. Defaults
   * to 'default' when this is not configured in settings.php
   *
   * @param string $name
   * @return string
   */
  public function getClusterDefaultBucketName(string $name = 'default') {
    $name = $this->settings['servers'][$name]['bucket'] ?? 'default';
    return $name;
  }

  /**
   * Gets the password used for SASL authentication
   * on the bucket.
   *
   * @param string $name
   * @return string|NULL
   */
  public function getClusterDefaultBucketPassword(string $name = 'default') {
    $password = $this->settings['servers'][$name]['bucket_password'] ?? NULL;
    return $password;
  }

  /**
   * Retrieve a connection to the couchbase server.
   *
   * @param string $name
   * @return bool|CouchbaseCluster
   */
  public function getCluster(string $name = 'default') {

    if (isset($this->clusters[$name])) {
      return $this->clusters[$name];
    }

    $url = $this->settings['servers'][$name]['uri'] ?? NULL;

    // For this service to work we need the CouchbaseCluster class.
    if (!class_exists(CouchbaseCluster::class) || empty($url)) {
      return FALSE;
    }

    $uri = new Uri($url);

    // There is a LOT of logic in the couchbase drupal driver
    // that depends on detailed error codes, so force them...
    $query = [];
    $query_string = $uri->getQuery();
    if (!empty($query_string)) {
      parse_str($query_string, $query);
    }

    if(isset($this->settings['servers'][$name]['uri_args']) && !empty($this->settings['servers'][$name]['uri_args'])){
      $query = array_merge($query, $this->settings['servers'][$name]['uri_args']);
    }

    $query = array_merge($query, ['detailed_errcodes' => '1']);
    $uri = $uri->withQuery(http_build_query($query));

    list($username, $password) = array_pad(explode(':', $uri->getUserInfo()), 2, NULL);
    if (!empty($server['username']) && !empty($server['password'])) {
      $uri = $uri->withUserInfo('');
      $this->clusters[$name] = new CouchbaseCluster($uri, $username, $password);
    }
    else {
      $this->clusters[$name] = new CouchbaseCluster($uri);
    }

    return $this->clusters[$name];
  }

  /**
   * Get a list of all available
   * bucket names.
   */
  public function listServers() {
    return array_keys($this->settings['servers']);
  }

  /**
   * Get a bucket trough their configuration ID.
   *
   * @param string $id
   * @param string $transcoder
   * @return Bucket
   */
  public function getBucketFromConfig($id, $transcoder = NULL) {
    $bucket_name = $this->getClusterDefaultBucketName($id);
    $bucket_password = $this->getClusterDefaultBucketPassword($id);
    return $this->getBucket($id, $bucket_name, $bucket_password, $transcoder);
  }

  /**
   * Retrieve a couchbase bucket.
   *
   * @param string $cluster_name
   *   The name of the cluster.
   *
   * @param string $bucket_name
   *   The name of the bucket.
   *
   * @param string $transcoder
   *   The class used as a transcoder.
   *
   * @return Bucket
   */
  public function getBucket(string $cluster_name = 'default', string $bucket_name = 'default', $password = NULL, $transcoder = NULL) {
    $cluster = $this->getCluster($cluster_name);
    // We will store instances of the bucket with
    // different transcoders.
    $key = implode('::', [$cluster_name, $bucket_name, $transcoder, $password]);
    if (!isset($this->buckets[$key])) {
      if ($transcoder) {
        if (!isset($this->transcoders[$transcoder])) {
          $this->transcoders[$transcoder] = new $transcoder();
        }
        $transcoder = $this->transcoders[$transcoder];
      }
      $this->buckets[$key] = new Bucket($cluster, $bucket_name, $password, $transcoder);
    }
    return $this->buckets[$key];
  }
}
