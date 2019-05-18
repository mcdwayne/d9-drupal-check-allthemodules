<?php

namespace Drupal\applenews;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;

/**
 * Class ApplenewsPreviewBuilder.
 *
 * This class generate downloadable Apple News Native formatted documents with
 * assets.
 *
 * @package Drupal\applenews
 */
class ApplenewsPreviewBuilder {

  /**
   * URI to individual entity export directory.
   *
   * @var string
   */
  private $entityDirectory;

  /**
   * Real path to individual entity export directory.
   *
   * @var string
   */
  private $entityRealPath;

  /**
   * Apple News article assets.
   *
   * @var array
   */
  private $files = [];

  /**
   * Apple News Native document formatted JSON string.
   *
   * @var int|string
   */
  private $articleJson;

  /**
   * Real path to the batch preview export archive file.
   *
   * @var string
   */
  private $archiveFile;

  /**
   * URL to the archive file.
   *
   * @var int|string
   */
  private $archiveUrl;

  /**
   * Real path to the batch preview directory.
   *
   * @var string
   */
  private $archiveRealPath;

  /**
   * Entity ID of the current preview object.
   *
   * @var int
   */
  private $entityId;

  /**
   * Indicates whether this is an individual entity export or not.
   *
   * @var bool
   */
  private $entityArchive;

  /**
   * URI to the main Apple News export directory.
   *
   * @var string
   */
  private $directory;

  /**
   * Batch preview export archive file name.
   *
   * @var string
   */
  private $archive = '';

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * ApplenewsPreviewBuilder constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   File system.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system) {
    $this->config = $config_factory->get('applenews.settings');
    $this->fileSystem = $file_system;
  }

  /**
   * Sets an entity.
   *
   * @param int $entity_id
   *   Integer entity ID.
   * @param string $filename
   *   String file name.
   * @param bool $entity_archive
   *   Flag to specify if archive.
   * @param array $data
   *   An array of article data.
   *
   * @return $this
   */
  public function setEntity($entity_id, $filename, $entity_archive = FALSE, array $data = []) {
    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem = $this->fileSystem;
    $this->directory = 'applenews_preview/';
    $this->archive = !empty($filename) ? 'applenews-' . $filename . '.zip' : 'applenews.zip';
    $this->archiveRealPath = $filesystem->realpath($this->fileBuildUri($this->directory));
    $this->archiveFile = $filesystem->realpath($this->fileBuildUri($this->directory . $this->archive));
    $this->archiveUrl = file_create_url($this->fileBuildUri($this->directory . $this->archive));

    if ($entity_id) {
      $drupal_entity_directory = $this->fileBuildUri($this->directory . $entity_id);
      $this->entityDirectory = $drupal_entity_directory;
      $this->entityRealPath = $filesystem->realpath($drupal_entity_directory);
      // Boolean value that indicated if we should create tmp archive file
      // for an entity.
      $this->entityArchive = $entity_archive;
      $this->entityId = $entity_id;
      if (count($data)) {
        $this->files = $data['files'];
        $this->articleJson = $data['json'];
      }
      if ($entity_archive) {
        $this->removeDirectories([$this->entityId]);
      }
      file_prepare_directory($drupal_entity_directory, FILE_CREATE_DIRECTORY);
    }
    return $this;

  }

  /**
   * Removes directories.
   *
   * @param array $entity_ids
   *   An array of entity IDs.
   */
  public function removeDirectories(array $entity_ids = []) {
    if (is_dir($this->archiveRealPath)) {
      foreach ($this->scanDirectory($this->archiveRealPath) as $file) {
        $dir = $this->archiveRealPath . '/' . $file;
        if (is_dir($dir) && in_array($file, $entity_ids)) {
          file_unmanaged_delete_recursive($dir);
        }
      }
    }
  }

  /**
   * Delete individual entity archive.
   *
   * @param int $entity_id
   *   Entity ID.
   */
  public function entityArchiveDelete($entity_id) {
    $archiveFile = $this->archiveRealPath . '/' . $entity_id . '.zip';
    file_unmanaged_delete_recursive($archiveFile);
  }

  /**
   * Returns URL path to an archive file.
   *
   * @return int|string
   *   String URL.
   */
  public function getArchiveFilePath() {
    return $this->archiveUrl;
  }

  /**
   * Get path to the individual entity archive.
   *
   * @param int $entity_id
   *   Entity ID.
   *
   * @return string
   *   String archive path.
   */
  public function getEntityArchivePath($entity_id) {
    return $this->archiveRealPath . '/' . $entity_id;
  }

  /**
   * Export entities to files.
   */
  public function toFile() {
    $this->saveArticleJson();
    $this->saveArticleAssets();
  }

  /**
   * Generate downloadable zip file.
   *
   * @param array $entity_ids
   *   An array of entity IDs.
   *
   * @throws \Exception
   */
  public function archive(array $entity_ids = []) {
    $this->createArchive($entity_ids);
  }

  /**
   * Helper to build file URI.
   *
   * @param string $path
   *   String path.
   *
   * @return string
   *   String full path after normalized.
   */
  protected function fileBuildUri($path) {
    $uri = 'temporary://' . $path;
    return file_stream_wrapper_uri_normalize($uri);
  }

  /**
   * Convert \ZipArchive::open() error code to message.
   *
   * @param int $error_code
   *   Integer error code.
   *
   * @see http://php.net/manual/en/ziparchive.open.php
   *
   * @return string
   *   String error message.
   */
  protected function zipErrorMsg($error_code) {
    switch ($error_code) {

      case \ZipArchive::ER_EXISTS:
        return 'File already exists.';

      case \ZipArchive::ER_INCONS:
        return 'Zip archive inconsistent.';

      case \ZipArchive::ER_INVAL:
        return 'Invalid argument.';

      case \ZipArchive::ER_MEMORY:
        return 'Malloc failure.';

      case \ZipArchive::ER_NOENT:
        return 'No such file.';

      case \ZipArchive::ER_NOZIP:
        return 'Not a zip archive.';

      case \ZipArchive::ER_OPEN:
        return 'Can\'t open file.';

      case \ZipArchive::ER_READ:
        return 'Read error.';

      case \ZipArchive::ER_SEEK:
        return 'Seek error.';

    }
    return 'Unknown error.';
  }

  /**
   * Save JSON string into article.json file.
   */
  private function saveArticleJson() {
    file_unmanaged_save_data($this->articleJson, $this->entityDirectory . '/article.json');
  }

  /**
   * Save article assets into article directory.
   */
  private function saveArticleAssets() {
    foreach ($this->files as $url => $path) {
      $contents = file_get_contents($path);
      file_unmanaged_save_data($contents, $this->entityDirectory . '/' . basename($url));
    }
  }

  /**
   * Scan a directory and return a list of file names and directories.
   *
   * @param string $path
   *   String path.
   *
   * @return array
   *   An array of files.
   */
  private function scanDirectory($path) {
    $items = array_values(array_filter(scandir($path), function ($file) {
      return !is_dir($file);
    }));
    return $items;
  }

  /**
   * Create [article-id].zip file archive.
   *
   * @param array $entity_ids
   *   An array of entity IDs.
   *
   * @throws \Exception
   */
  private function createArchive(array $entity_ids = []) {

    // Start creating a new archive file.
    if (!class_exists('\ZipArchive')) {
      throw new \Exception('Feature requires PHP Zip extension.');
    }
    $zip = new \ZipArchive();

    if ($this->entityArchive) {

      $entity_archiveRealPath = $this->archiveRealPath . '/' . $this->entityId;
      $entity_archive = $entity_archiveRealPath . '.zip';

      // Make sure to remove archive file first.
      if (file_exists($entity_archive)) {
        file_unmanaged_delete($entity_archive);
      }

      // Open archive.
      $result = $zip->open($entity_archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
      if ($result !== TRUE) {
        throw new \Exception('Could not open archive file: ' . $this->zipErrorMsg($result));
      }
      // Create an archive of an article assets and content.
      foreach ($this->scanDirectory($entity_archiveRealPath) as $item) {
        $zip->addFile($entity_archiveRealPath . '/' . $item, $this->entityId . '/' . $item);
      }

    }
    else {

      // Open archive.
      $result = $zip->open($this->archiveFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
      if ($result !== TRUE) {
        throw new \Exception('Could not open archive file: ' . $this->zipErrorMsg($result));
      }

      // Scan through all entity directories and add each file to an archive.
      foreach ($this->scanDirectory($this->archiveRealPath) as $item) {
        $dir = $this->archiveRealPath . '/' . $item;
        if (is_dir($dir) && in_array($item, $entity_ids)) {
          $zip->addEmptyDir($item);
          $files = $this->scanDirectory($this->archiveRealPath . '/' . $item);
          foreach ($files as $file) {
            $zip->addFile($this->archiveRealPath . '/' . $item . '/' . $file, $item . '/' . $file);
          }
        }
      }
    }

    // Close and save archive.
    $zip->close();

  }

}
