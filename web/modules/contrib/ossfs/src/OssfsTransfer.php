<?php

namespace Drupal\ossfs;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\StreamWrapper\PublicStream;
use OSS\Core\MimeTypes;
use OSS\Core\OssException;
use OSS\OssClient;

class OssfsTransfer {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The ossfs configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * The ossfs cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The OSS client.
   *
   * @var \OSS\OssClient
   */
  protected $client;

  /**
   * Constructs a Transfer object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The ossfs cache backend.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->connection = $connection;
    $this->config = $config_factory->get('ossfs.settings')->get();
    unset($this->config['_core']);
    $this->cache = $cache;
  }

  /**
   * Returns the OSS client.
   *
   * @return \OSS\OssClient
   */
  protected function getClient() {
    if (!$this->client) {
      $endpoint = $this->config['region'] . ($this->config['internal'] ? '-internal' : '') . '.aliyuncs.com';
      $this->client = new OssClient($this->config['access_key'], $this->config['secret_key'], $endpoint, FALSE);
    }
    return $this->client;
  }

  /**
   * Validates the ossfs config.
   *
   * @return bool|string
   *   TRUE if the configuration is valid, an error message when failure occurs.
   */
  public function validateConfig() {
    if (!class_exists('\OSS\OssClient')) {
      return 'Cannot load OSS\OssClient class. Please ensure that the oss sdk php library is installed correctly.';
    }
    if (!$this->config['secret_key'] || !$this->config['access_key']) {
      return 'Your Aliyun credentials have not been properly configured.';
    }
    if (empty($this->config['bucket'])) {
      return 'Your bucket name is not configured.';
    }
    if (empty($this->config['region'])) {
      return 'Your region is not configured.';
    }

    // Test the connection to OSS, and the bucket name.
    try {
      // listObjects() will trigger descriptive exceptions if the credentials,
      // bucket name, or region are invalid/mismatched.
      $this->getClient()->listObjects($this->config['bucket'], [
        'max-keys' => 1,
      ]);
    }
    catch (OssException $e) {
      return 'An unexpected error occurred ' . $e->getMessage();
    }

    return TRUE;
  }

  /**
   * Uploads "public://" local files into OSS.
   *
   * @param string $sub_dir
   *   The sub directory to upload, if empty the root "public://" directory will
   *   be used.
   * @param bool $recursive
   *   Upload files recursively.
   *
   * @return \Iterator
   *   Upload messages.
   */
  public function uploadPublic($sub_dir, $recursive) {
    $base_path = PublicStream::basePath();
    $dir = $base_path . ($sub_dir === '' ? '' : '/' . $sub_dir);
    $key_start = strlen($base_path) + 1; // e.g.: strlen('public://sites/default/files' . '/');

    $i = 0;
    foreach ($this->scanDirectory($dir, $recursive) as $path) {
      $destination = 'oss://' . substr($path, $key_start);
      $i++;
      yield "[$i] $path\t\t==>\t$destination";

      // Make use of OssfsStream's functionality.
      // Check if directory exists.
      $dir = drupal_dirname($destination);
      if ($dir !== 'oss://' && !is_dir($dir)) {
        @drupal_mkdir($dir, NULL, TRUE);
      }

      copy($path, $destination);
    }
  }

  /**
   * Scans a given directory recursively.
   *
   * @param string $dir
   *   The directory to be scanned.
   * @param bool $recursive
   *   Whether or not to scan recursively.
   *
   * @return array
   *   An array of full file paths, not including directories.
   */
  protected function scanDirectory($dir, $recursive) {
    static $ignore_dirs = NULL;
    if ($ignore_dirs === NULL) {
      $ignore_dirs = [
        config_get_config_directory('sync'),
      ];
    }

    $output = [];
    foreach (scandir($dir) as $filename) {
      if (in_array($filename, ['.', '..', '.htaccess'], TRUE)) {
        continue;
      }

      $path = "$dir/$filename";
      if (is_dir($path)) {
        if ($recursive && !in_array($path, $ignore_dirs, TRUE)) {
          $output = array_merge($output, $this->scanDirectory($path, $recursive));
        }
      }
      else {
        $output[] = $path;
      }
    }
    return $output;
  }

  /**
   * Syncs OSS object metadata to the local storage.
   *
   * Iterates over the full list of objects in the OSS bucket, saves their
   * metadata in the storage, and saves the ancestor folders for those files
   * since folders are not normally stored as actual objects in OSS.
   *
   * @return int
   *   The number of records affected.
   */
  public function syncMetadata() {
    $schema = $this->connection->schema();
    $sync_table = 'ossfs_file_sync';

    try {
      // Create the sync table.
      $table_schema = drupal_get_module_schema('ossfs', 'ossfs_file');
      $schema->createTable($sync_table, $table_schema);
    } 
    catch (SchemaObjectExistsException $e) {
      // The table already exists, so we can simply truncate it to start fresh.
      $this->connection->truncate($sync_table)->execute();
    }

    // Fetch all the objects' info from OSS.
    $prefix = (string) $this->config['prefix'];
    $prefix = $prefix === '' ? '' : UrlHelper::encodePath($prefix) . '/';
    $next_marker = '';
    do {
      // Set 'delimiter' to '' to fetch objects recursively in sub directories
      // as well.
      $result = $this->getClient()->listObjects($this->config['bucket'], [
        'delimiter' => '',
        'prefix' => $prefix,
        'max-keys' => 1000,
        'marker' => $next_marker,
      ]);

      // Write files to the sync table.
      $this->writeSyncFiles($sync_table, $result->getObjectList(), $prefix);
      // Update the next marker.
      $next_marker = $result->getNextMarker();
    } while ($next_marker !== '');

    // Write directories to the sync table according to the saved file info.
    $this->writeSyncDirectories($sync_table);

    // Swap the sync table with the real table.
    $schema->renameTable('ossfs_file', 'ossfs_file_old');
    $schema->renameTable($sync_table, 'ossfs_file');
    $schema->dropTable('ossfs_file_old');

    // Delete all cached items from cache backend.
    $this->cache->deleteAll();

    return (int) $this->connection->select('ossfs_file')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  protected function writeSyncFiles($table, array $objects, $prefix) {
    $prefix_length = strlen($prefix);

    // Write the file metadata to the local storage.
    $insert = $this->connection->insert($table)
      ->fields(['uri', 'type', 'filemime', 'filesize', 'imagesize', 'changed']);
    foreach ($objects as $object) {
      $key = $object->getKey();
      if (substr($key, -1) == '/') {
        // Ignore directory.
        continue;
      }

      if ($prefix_length > 0) {
        $key = substr($key, $prefix_length);
      }
      $data = [
        'uri' => 'oss://' . $key,
        'type' => 'file',
        'filemime' => MimeTypes::getMimetype($key) ?: OssClient::DEFAULT_CONTENT_TYPE,
        'filesize' => $object->getSize(),
        'imagesize' => '',
        'changed' => strtotime($object->getLastModified()),
      ];
      $insert->values($data);
    }
    $insert->execute();
  }

  protected function writeSyncDirectories($table) {
    $dir_paths = [];

    $uris = $this->connection->select($table, 'of')
      ->fields('of', ['uri'])
      ->execute()
      ->fetchCol();

    // Strip the leading 'oss://' and trailing basename.
    $paths = array_map(function ($uri) {
      $dirname = dirname(substr($uri, 6));
      return $dirname == '.' ? '' : $dirname;
    }, $uris);
    $paths = array_filter(array_unique($paths));

    foreach ($paths as $path) {
      $components = explode('/', $path);
      $recursive_path = '';
      foreach ($components as $component) {
        $recursive_path .= $component;
        $dir_paths[$recursive_path] = TRUE;
        $recursive_path .= '/';
      }
    }

    // Write the directory metadata to the local storage.
    $insert = $this->connection->insert($table)
      ->fields(['uri', 'type', 'filemime', 'filesize', 'imagesize', 'changed']);
    foreach (array_keys($dir_paths) as $path) {
      $data = [
        'uri' => 'oss://' . $path,
        'type' => 'dir',
        'filemime' => '',
        'filesize' => 0,
        'imagesize' => '',
        'changed' => REQUEST_TIME,
      ];;
      $insert->values($data);
    }
    $insert->execute();
  }

}
