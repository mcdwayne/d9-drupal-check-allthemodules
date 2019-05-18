<?php

namespace Drupal\environmental_config;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TmpConfigFolderManager.
 *
 * @package Drupal\environmental_config
 */
class TmpConfigFolderManager implements ContainerInjectionInterface {

  const TMP_FOLDER_NAME = 'environmental_config';

  /**
   * The environment.
   *
   * @var string
   */
  protected $environment;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The base sync folder.
   *
   * @var string
   */
  protected $originalSyncFolder;

  /**
   * The tmp config destination that contains env and base config files.
   *
   * @var string
   */
  protected $tmpConfigDestination;

  /**
   * The environmental config folder for the current environment.
   *
   * @var string
   */
  protected $environmentConfigFolder;

  /**
   * The flag if the update of the tmp folder should be force.
   *
   * @var bool
   */
  protected $forceUpdate;

  /**
   * The environmental file list report.
   *
   * @var array
   */
  protected $environmentalFilesList = ['added' => [], 'not_added' => []];

  /**
   * TmpConfigFolderManager constructor.
   *
   * @param string $env
   *   The detected environment.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param string $originalSyncfolder
   *   The Drupal base sync folder.
   */
  public function __construct($env, LoggerInterface $logger, CacheBackendInterface $cacheBackend, $originalSyncfolder = CONFIG_SYNC_DIRECTORY) {
    $this->environment = is_string($env) && $this->overrideEnv($env) ? $env : NULL;
    $this->logger = $logger;
    $this->originalSyncFolder = $this->getConfigDirectory($originalSyncfolder);
    $this->cacheBackend = $cacheBackend;
    $this->determineTmpConfigDestination();
    $this->determineEnvironmentalFilesList();
    $this->determineEnvironmentConfigFolder();
  }

  /**
   * Overrides the detected env faking the whole detection system.
   *
   * To be used only if you know what you are doing.
   *
   * @param string $env
   *   The env.
   *
   * @return bool
   *   The return.
   */
  public function overrideEnv($env) {
    if ($this->getConfigDirectory($env)) {
      $this->environment = $env;
      $this->environmentConfigFolder = $this->getConfigDirectory($env);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * ContainerInjectionInterface requirement.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    $envDetector = new EnvironmentDetector($container->get('plugin.manager.environmental_config.environmentdetectormanager'));

    return new static($envDetector->detect(),
                      $container->get('logger.channel.environmental_config'),
                      $container->get('cache.environmental_config'),
                      CONFIG_SYNC_DIRECTORY);
  }

  /**
   * Determines which folder to use: the temp or the base sync folder.
   *
   * @param bool $forceUpdate
   *   The flag to force the update of the temp folder.
   *
   * @return string
   *   The tmp folder or the original sync folder name.
   */
  public function determineFolder($forceUpdate = FALSE) {
    // Leverage on cache to check the environmentalFilesList and the path.
    $forceUpdate = $forceUpdate || $this->forceUpdate || !$this->tmpConfigDestination ? TRUE : $forceUpdate;
    if ($this->environment && $this->checkCurrentEnvironmentFolder($forceUpdate)) {
      return $this->tmpConfigDestination;
    }

    return $this->originalSyncFolder;
  }

  /**
   * Check the current environment validity.
   *
   * Check whether the actual environment has a corresponding folder
   * and so is to be considered a valid environment.
   *
   * @return bool
   *   FALSE if the environment is not valid.
   */
  public function checkEnvironmentValidity() {
    return $this->environment && $this->checkCurrentEnvironmentFolder();
  }

  /**
   * Rebuilds the temp folder.
   *
   * @return bool
   *   The return.
   */
  public function rebuildTmpFolder() {
    if (!$this->environment) {
      return FALSE;
    }

    // Clearing current cached elements.
    $this->cacheBackend->invalidateAll();
    $this->cacheBackend->garbageCollection();

    $this->determineTmpConfigDestination();
    if (is_dir($this->tmpConfigDestination)) {
      self::rrmdir($this->tmpConfigDestination);
    }

    if (!is_dir($this->tmpConfigDestination)) {
      mkdir($this->tmpConfigDestination, 0777, TRUE);
    }

    // Copy first the main configuration in the tmp folder.
    $this->copy($this->originalSyncFolder, $this->tmpConfigDestination);

    // Override the files related to this env in the tmp folder.
    $this->environmentalFilesList = $this->copy($this->environmentConfigFolder, $this->tmpConfigDestination);
    $this->cacheBackend->set('tmp_environmental_files_list_' . $this->environment,
                             $this->environmentalFilesList,
                             CacheBackendInterface::CACHE_PERMANENT);

    if (count($this->environmentalFilesList['added']) <= 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the detected environment.
   *
   * @return null|string
   *   The return.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Returns the path of the current environment config.
   *
   * @return string|false
   *   The path or FALSE
   */
  public function getEnvironmentConfigDir() {
    return $this->getConfigDirectory($this->environment);
  }

  /**
   * Gets the list of the files to change for the current env.
   *
   * @return array
   *   The return.
   */
  public function getEnvironmentalFilesList() {
    return $this->environmentalFilesList;
  }

  /**
   * Gets the last build time of the tmp folder for the current env.
   *
   * @return null|string
   *   The return.
   */
  public function getLastTmpFolderBuildTime() {
    if ($cache = $this->cacheBackend->get('tmp_config_destination_last_build_' . $this->environment)) {
      return (string) $cache->data;
    }
    return NULL;
  }

  /**
   * Determines the temp config destination for the current env.
   *
   * Determines and caches the tmp folder path where
   * to store the final config folder.
   */
  protected function determineTmpConfigDestination() {
    if (!$this->environment) {
      return;
    }
    if ($cache = $this->cacheBackend->get('tmp_config_destination_' . $this->environment)) {
      $this->tmpConfigDestination = $cache->data;
    }
    else {
      $this->tmpConfigDestination = $this->buildTmpDestinationPath();
      $this->cacheBackend->set('tmp_config_destination_' . $this->environment, $this->tmpConfigDestination, CacheBackendInterface::CACHE_PERMANENT);
      $this->cacheBackend->set('tmp_config_destination_last_build_' . $this->environment, time(), CacheBackendInterface::CACHE_PERMANENT);
      $this->forceUpdate = TRUE;
    }
  }

  /**
   * Determines the environmental file list to apply for the current env.
   *
   * Determines the files that will be overridden for
   * the current environment.
   */
  protected function determineEnvironmentalFilesList() {
    if (!$this->environment) {
      return;
    }
    if ($cache = $this->cacheBackend->get('tmp_environmental_files_list_' . $this->environment)) {
      $this->environmentalFilesList = $cache->data;
    }
  }

  /**
   * Determines the physical config folder for the current env.
   */
  protected function determineEnvironmentConfigFolder() {
    if (!$this->environment) {
      return;
    }
    if ($cache = $this->cacheBackend->get('tmp_environmental_config_folder_' . $this->environment)) {
      $this->environmentConfigFolder = $cache->data;
    }
    else {
      $this->environmentConfigFolder = $this->getConfigDirectory($this->environment);
      $this->cacheBackend->set('tmp_environmental_config_folder_' . $this->environment,
                               $this->environmentConfigFolder,
                               CacheBackendInterface::CACHE_PERMANENT);
    }
  }

  /**
   * Checks the current environment folder it is a dir.
   *
   * @param bool $tmpFolderRebuild
   *   The flag to determine if a tmp folder should be rebuilt.
   *
   * @return bool
   *   The return.
   */
  protected function checkCurrentEnvironmentFolder($tmpFolderRebuild = FALSE) {
    // If the folder is set, send straight away.
    if ($tmpFolderRebuild) {
      $this->rebuildTmpFolder();
    }

    if (is_dir($this->tmpConfigDestination)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Copies a folder source into a folder destination recursively.
   *
   * @param string $source
   *   The path of the source directory.
   * @param string $destination
   *   The path of the destination directory.
   *
   * @return array
   *   The return.
   */
  protected function copy($source, $destination) {
    $source = $source ? rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : NULL;
    $destination = $destination ? rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : NULL;
    $processed = [];
    $processed['added'] = [];
    $processed['not_added'] = [];
    if ($source && $destination && is_dir($source) && is_dir($destination) && is_writable($destination)) {
      $files = new \DirectoryIterator($source);
      foreach ($files as $fileInfo) {
        $filename = $fileInfo->getFilename();
        // Recursively copy a folder by keeping the $processed index updated.
        if (!$fileInfo->isDot() && $fileInfo->isDir()) {
          // Tries to create the subfolder.
          @mkdir($destination . $filename, 0777, TRUE);
          $subDirProcessed = self::copy($source . $filename, $destination . $filename);
          $processed = array_merge_recursive($processed, $subDirProcessed);
          continue;
        }
        if ($fileInfo->isDot() || 0 === strpos($filename, '.') || $fileInfo->isDir()) {
          continue;
        }
        if (@copy($source . $filename, $destination . $filename)) {
          $processed['added'][] = $source . $filename;
        }
        else {
          $processed['not_added'][] = $source . $filename;
        }
      }
    }
    return $processed;
  }

  /**
   * Returns the config directory path for the given environment.
   *
   * @param string $env
   *   The environment name.
   *
   * @return string
   *   The return.
   */
  protected function getConfigDirectory($env) {
    try {
      return config_get_config_directory($env);
    }
    catch (\Exception $e) {
      $this->logger->error('environmental_config error while trying to retrieve config directory: %message', ['%message' => $e->getMessage()]);
    }
    return FALSE;
  }

  /**
   * Gets the drupal temporary directory path.
   *
   * @return mixed|null
   *   The path.
   */
  protected function getTempDirectory() {
    return file_directory_temp();
  }

  /**
   * Builds a tmp destination path for the current environment.
   *
   * @return string
   *   The return.
   */
  protected function buildTmpDestinationPath() {
    $tmp = $this->getTempDirectory();
    $destination = $tmp . DIRECTORY_SEPARATOR . self::TMP_FOLDER_NAME . DIRECTORY_SEPARATOR . $this->environment;
    return $destination;
  }

  /**
   * Recursively remove a dir.
   *
   * @param string $dir
   *   The directory.
   *
   * @return bool
   *   The return.
   */
  protected static function rrmdir($dir) {
    if (is_dir($dir)) {
      $ds = DIRECTORY_SEPARATOR;
      $files = new \DirectoryIterator($dir);
      foreach ($files as $fileInfo) {
        if (!$fileInfo->isDot()) {
          $filename = $fileInfo->getFilename();
          if ($fileInfo->isDir()) {
            self::rrmdir($dir . $ds . $filename);
          }
          else {
            unlink($dir . $ds . $filename);
          }
        }
      }
      return rmdir($dir);
    }
    return FALSE;
  }

}
