<?php

namespace Drupal\gclient_storage;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a GclientStorageService service.
 */
class GclientStorageService implements GclientStorageServiceInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs an GclientStorageService object.
   *
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The new database connection object.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(Connection $database_connection, ConfigFactory $config_factory) {
    $this->databaseConnection = $database_connection;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convertMetadata($uri, $object_metadata) {
    // Need to fill in a default value for everything, so that DB calls
    // won't complain about missing fields.
    $metadata = [
      'uri' => $uri,
      'url' => '',
      'version' => '',
      'filemime' => '',
      'filesize' => 0,
      'timestamp' => \Drupal::time()->getRequestTime(),
      'dir' => 0,
    ];

    if (empty($object_metadata)) {
      // The caller wants directory metadata.
      $metadata['dir'] = 1;
    }
    else {
      $metadata['url'] = 'https://storage.googleapis.com/' . $object_metadata->bucket . '/' . $object_metadata->name;

      if (isset($object_metadata->contentType)) {
        $metadata['filemime'] = $object_metadata->contentType;
      }

      if (isset($object_metadata->size)) {
        $metadata['filesize'] = $object_metadata->size;
      }

      if (isset($object_metadata->updated)) {
        $metadata['timestamp'] = date('U', strtotime($object_metadata->updated));
      }

      if (isset($object_metadata->generation)) {
        $metadata['version'] = $object_metadata->generation;
      }
    }

    return $metadata;
  }

}
