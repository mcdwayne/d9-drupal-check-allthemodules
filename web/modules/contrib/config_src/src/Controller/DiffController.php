<?php

namespace Drupal\config_src\Controller;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Url;
use Drupal\config\Controller\ConfigController;
use Drupal\Core\Config\FileStorage;

/**
 * Returns responses for config module routes.
 */
class DiffController extends ConfigController {
  /**
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage.
   */
  private function setSourceStorage(StorageInterface $source_storage) {
    $this->sourceStorage = $source_storage;
  }

  /**
   * Shows the diff of the specified configuration file.
   *
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   * @param string $config_source
   *   The name of the configuration source directory.
   *
   * @return string
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL, $config_source = NULL) {
    $build = [];

    if (!empty($config_source) && isset($GLOBALS['config_directories'][$config_source])) {
      $config_storage = new FileStorage($GLOBALS['config_directories'][$config_source]);
      $this->setSourceStorage($config_storage);

      $build = parent::diff($source_name, $target_name, $collection);

      $options = array(
        'query' => ['config_source' => $config_source],
      );

      $build['back'] = array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'dialog-cancel',
          ),
        ),
        '#title' => "Back to the 'Synchronize configuration' page.",
        '#url' => Url::fromRoute('config.sync', array(), $options),
      );
    }

    return $build;
  }

}
