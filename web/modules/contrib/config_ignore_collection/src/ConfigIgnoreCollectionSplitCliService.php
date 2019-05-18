<?php

namespace Drupal\config_ignore_collection;

use Drupal\config_split\ConfigSplitCliService;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageInterface;

/**
 * Class ConfigIgnoreCollectionSplitCliService.
 *
 * @package Drupal\config_ignore_collection
 */
class ConfigIgnoreCollectionSplitCliService extends ConfigSplitCliService {

  /**
   * Import the configuration.
   *
   * This is the quintessential config import.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to import from.
   *
   * @return string
   *   The state of importing.
   */
  public function import(StorageInterface $storage) {

    $comparer = new StorageComparer($storage, $this->activeStorage, $this->configManager);

    if (!$comparer->createChangelist()->hasChanges()) {
      return static::NO_CHANGES;
    }

    $importer = new ConfigImporter(
      $comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation
    );

    if ($importer->alreadyImporting()) {
      return static::ALREADY_IMPORTING;
    }

    try {
      // Do the import with the ConfigImporter.
      $importer->import();
    }
    catch (ConfigImporterException $e) {
      // Catch and re-trow the ConfigImporterException.
      $this->errors = $importer->getErrors();
      throw $e;
    }

    return static::COMPLETE;
  }

}
