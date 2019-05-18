<?php

namespace Drupal\remote_config_sync\Service;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Sync.
 */
class Sync {

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * Sync constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   */
  public function __construct(ConfigManagerInterface $config_manager, StorageInterface $target_storage) {
    $this->configManager = $config_manager;
    $this->targetStorage = $target_storage;
  }

  /**
   * Push the configuration to a remote site.
   *
   * @param string $remote
   * @param bool $import
   *
   * @return array
   */
  public function push($remote, $import = FALSE) {
    $remote = explode('|', $remote);
    $remote_url = $remote[0];
    $remote_token = $remote[1];

    if ($this->exportConfig()) {
      return $this->uploadFile($remote_url, $remote_token, $import);
    }

    return [
      'status' => 'error',
      'message' => $this->t('Error while exporting the configuration.'),
    ];
  }

  /**
   * Export the configuration to a .tar.gz archive file.
   *
   * @return bool
   */
  protected function exportConfig() {
    file_unmanaged_delete(file_directory_temp() . '/remote_config_sync.tar.gz');
    $archiver = new ArchiveTar(file_directory_temp() . '/remote_config_sync.tar.gz', 'gz');

    // Get raw configuration data without overrides.
    foreach ($this->configManager->getConfigFactory()->listAll() as $name) {
      if ($name == 'remote_config_sync.settings') {
        continue;
      }
      $archiver->addString("$name.yml", Yaml::encode(
        $this->configManager->getConfigFactory()->get($name)->getRawData()
      ));
    }

    // Get all override data from the remaining collections.
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode(
          $collection_storage->read($name)
        ));
      }
    }

    if (file_exists(file_directory_temp() . '/remote_config_sync.tar.gz')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Upload the configuration archive file to a remote site.
   *
   * @param string $remote_url
   * @param string $remote_token
   * @param bool $import
   *
   * @return array
   */
  protected function uploadFile($remote_url, $remote_token, $import) {
    $file_path = file_directory_temp() . '/remote_config_sync.tar.gz';
    $hash = hash_file('md5', $file_path);

    try {
      $client = new Client();
      $response = $client->post(rtrim($remote_url, '/') . '/api/v1/remote-config-sync', [
        'headers' => [
          'token' => $remote_token,
          'hash' => $hash,
          'import' => $import,
        ],
        'body' => file_get_contents(file_directory_temp() . '/remote_config_sync.tar.gz'),
      ]);
      $response_contents = json_decode($response->getBody()->getContents(), TRUE);
      return [
        'status' => $response_contents['status'],
        'message' => $response_contents['message'],
        'host' => $response_contents['host'],
      ];
    }
    catch (RequestException $e) {
      return [
        'status' => 'error',
        'message' => t('Error while pushing the configuration') . ': ' . $e->getMessage(),
      ];
    }
  }

}
