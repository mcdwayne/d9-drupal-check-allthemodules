<?php

namespace Drupal\acquiadam_asset_import;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\media_acquiadam\Acquiadam;
use Drupal\Core\Database\Connection;

/**
 * A Class.
 */
class DamImporter {

  /**
   * A dam client.
   *
   * @var \Drupal\media_acquiadam\Acquiadam
   */
  protected $acquiadam;

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * A constructor.
   */
  public function __construct(Acquiadam $acquiadam, QueueFactory $queueFactory, Connection $connection, ConfigFactory $config) {
    $this->acquiadam = $acquiadam;
    $this->queue = $queueFactory;
    $this->connection = $connection;
    $this->config = $config;
  }

  /**
   * Imports assets.
   */
  public function import() {
    $queue = $this->queue->get('dam_worker');
    if ($queue->numberOfItems() <= 0) {
      // Get a list of folder ids from config.
      $folders_config = $this->config->get('acquiadam_asset_import.config')->get('folders');
      $enable = $this->config->get('acquiadam_asset_import.config')->get('enable');

      if (empty($folders_config) || $enable == FALSE) {
        return;
      }

      $folder_ids = explode("\r\n", $folders_config);

      $imported_assets = $this->connection
        ->select('media__field_acquiadam_asset_id', 'ad')
        ->fields('ad', ['field_acquiadam_asset_id_value'])
        ->execute()
        ->fetchCol();

      foreach ($folder_ids as $folder_id) {
        $data = $this->acquiadam->searchAssets([
          'query' => '',
          'folderid' => $folder_id,
          'limit' => 1,
        ]);

        for ($i = 0; $i < $data['total_count']; $i + 100) {
          $assets = $this->acquiadam->searchAssets([
            'query' => '',
            'folderid' => $folder_id,
            'limit' => 100,
            'offset' => $i,
          ]);

          foreach ($assets['assets'] as $asset) {
            // Reject any assets that already exist in Drupal.
            if (!in_array($asset->id, $imported_assets)) {
              $item = [
                'asset_id' => $asset->id,
                'name' => $asset->name,
              ];
              $queue->createItem($item);
            }
          }
          $i = $i + 100;
        }
      }
    }
    return $queue->numberOfItems();
  }

}
