<?php

namespace Drupal\config_export_ignore;

use Drupal\config_filter\ConfigFilterManagerInterface;
use Drupal\config_filter\ConfigFilterStorageFactory;
use Drupal\config_split\ConfigSplitCliService;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Overrides exports service and removes specified configuration form export.
 *
 * @package Drupal\config_export_ignore
 */
class ConfigExportIgnoreConfigSplitService extends ConfigSplitCliService {

  const FORCE_EXCLUSION_PREFIX = '~';

  /**
   * ConfigExportIgnoreConfigSplitService constructor.
   */
  public function __construct(ConfigFilterManagerInterface $config_filter_manager, ConfigFilterStorageFactory $storageFactory, ConfigManagerInterface $config_manager, StorageInterface $active_storage, StorageInterface $sync_storage, EventDispatcherInterface $event_dispatcher, LockBackendInterface $lock, TypedConfigManagerInterface $config_typed, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ThemeHandlerInterface $theme_handler, TranslationInterface $string_translation) {
    parent::__construct($config_filter_manager, $storageFactory, $config_manager, $active_storage, $sync_storage, $event_dispatcher, $lock, $config_typed, $module_handler, $module_installer, $theme_handler, $string_translation);
  }

  /**
   * Export the configuration.
   *
   * This is the quintessential config export.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to export to.
   * 
   * @param \Drupal\Core\Config\StorageInterface|null $active
   *   The config storage to export from (optional).
   */
  public function export(StorageInterface $storage, StorageInterface $active = NULL) {

    // Delete all, the filters are responsible for keeping some configuration.
    $storage->deleteAll();

    // Get the default active storage to copy it to the sync storage.
    if ($this->activeStorage->getCollectionName() != StorageInterface::DEFAULT_COLLECTION) {
      // This is probably not necessary, but we do it as a precaution.
      $this->activeStorage = $this->activeStorage->createCollection(StorageInterface::DEFAULT_COLLECTION);
    }
    // Copy everything.
    foreach ($this->activeStorage->listAll() as $name) {
      if (!self::matchConfigName($name)) {
        $storage->write($name, $this->activeStorage->read($name));
      }
    }

    // Get all override data from the remaining collections.
    foreach ($this->activeStorage->getAllCollectionNames() as $collection) {
      $source_collection = $this->activeStorage->createCollection($collection);
      $destination_collection = $storage->createCollection($collection);
      // Delete everything in the collection sub-directory.
      try {
        $destination_collection->deleteAll();
      }
      catch (\UnexpectedValueException $exception) {
        // Deleting a non-existing folder for collections might fail.
      }

      foreach ($source_collection->listAll() as $name) {
        if (!self::matchConfigName($name)) {
          $destination_collection->write($name, $source_collection->read($name));
        }
      }

    }

  }

  /**
   * Match a config entity name against the list of ignored config entities.
   *
   * @param string $config_name
   *   The name of the config entity to match against all ignored entities.
   *
   * @return bool
   *   True, if the config entity is to be ignored, false otherwise.
   */
  public static function matchConfigName($config_name) {
    $config_ignore_settings = \Drupal::config('config_export_ignore.settings')->get('configuration_names');

    // If the string is an excluded config, don't ignore it.
    if (in_array(static::FORCE_EXCLUSION_PREFIX . $config_name, $config_ignore_settings, TRUE)) {
      return FALSE;
    }

    foreach ($config_ignore_settings as $config_ignore_setting) {
      // Test if the config_name is in the ignore list using a shell like
      // validation function to test the config_ignore_setting pattern.
      if (fnmatch($config_ignore_setting, $config_name)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
