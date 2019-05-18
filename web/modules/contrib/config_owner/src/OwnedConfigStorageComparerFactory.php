<?php

declare(strict_types = 1);

namespace Drupal\config_owner;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Generates a StorageComparer for the owned config.
 *
 * This service produces an instance of StorageComparer that compares the active
 * storage with a version of itself with the owned config applied.
 */
class OwnedConfigStorageComparerFactory {

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Active storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * Owned config manager.
   *
   * @var \Drupal\config_owner\OwnedConfigManagerInterface
   */
  protected $ownedConfigManager;

  /**
   * ConfigImportCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Config manager.
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   Active storage.
   * @param \Drupal\config_owner\OwnedConfigManagerInterface $ownedConfigManager
   *   Owned config manager.
   */
  public function __construct(ConfigManagerInterface $configManager, StorageInterface $activeStorage, OwnedConfigManagerInterface $ownedConfigManager) {
    $this->configManager = $configManager;
    $this->activeStorage = $activeStorage;
    $this->ownedConfigManager = $ownedConfigManager;
  }

  /**
   * Creates the StorageComparer instance.
   *
   * @return \Drupal\Core\Config\StorageComparer
   *   The storage comparer.
   */
  public function create() {
    $sync_storage = new MemoryConfigStorage(StorageInterface::DEFAULT_COLLECTION, $this->activeStorage);
    foreach ($this->activeStorage->listAll() as $name) {
      $sync_storage->write($name, $this->activeStorage->read($name));
    }

    $configs = $this->ownedConfigManager->getOwnedConfigValues();
    foreach ($configs as $name => $config) {
      $active = $this->activeStorage->read($name);
      $active = $active ? OwnedConfigHelper::replaceConfig($active, $config) : $config;
      $sync_storage->write($name, $active);
    }

    return new StorageComparer($sync_storage, $this->activeStorage, $this->configManager);
  }

}
