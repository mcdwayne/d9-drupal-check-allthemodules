<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process;

use Drupal\Component\Utility\Unicode;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Abstract class for Image HTML Tag processors.
 */
abstract class HtmlTagImgProcess extends HtmlTagProcess {

  /**
   * File entity retrieved after processing the file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The alt attribute of the image.
   *
   * @var string|null
   */
  protected $alt = NULL;

  /**
   * The title attribute of the image.
   *
   * @var string|null
   */
  protected $title = NULL;

  /**
   * The base path to the source, a website base URL or an absolute file path.
   *
   * @var string|null
   */
  protected $sourceBasePath = NULL;

  /**
   * The base URLs of the source website.
   *
   * @var array|null
   */
  protected $sourceBaseUrls = NULL;

  /**
   * The target folder on the destination.
   *
   * @var string|null
   */
  protected $targetFolder = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($this->configuration['source_base_path'])) {
      $this->setSourceBasePath($this->configuration['source_base_path']);
    }

    if (isset($this->configuration['source_base_url'])) {
      $sourceBaseUrls = $this->configuration['source_base_url'];

      if (!is_array($sourceBaseUrls)) {
        $sourceBaseUrls = [$sourceBaseUrls];
      }

      $this->setSourceBaseUrls($sourceBaseUrls);
    }

    if (isset($this->configuration['target_folder'])) {
      $this->setTargetFolder($this->configuration['target_folder']);
    }
  }

  /**
   * Return the file entity.
   *
   * @return \Drupal\file\FileInterface|false
   *   File entity.
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * Return the file entity ID.
   *
   * @return int|false
   *   File entity ID or false if not available.
   */
  public function getFileId() {
    if ($file = $this->getFile()) {
      if (is_subclass_of($file, 'Drupal\file\FileInterface')) {
        return $file->id();
      }
    }

    return FALSE;
  }

  /**
   * Set the file entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   File entity.
   */
  protected function setFile(FileInterface $file) {
    $this->file = $file;
  }

  /**
   * Return the alt attribute value.
   *
   * @return string|null
   *   Alt attribute or null if not set.
   */
  public function getAlt() {
    return $this->alt;
  }

  /**
   * Set the alt attribute value.
   *
   * @param string $alt
   *   Alt attribute.
   */
  protected function setAlt($alt) {
    $this->alt = $alt;
  }

  /**
   * Return the title attribute value.
   *
   * @return string|null
   *   Title attribute or null if not set.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Set the title attribute value.
   *
   * @param string $title
   *   Title attribute.
   */
  protected function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Return the source base path value.
   *
   * @return string|null
   *   Source base path or null if not set.
   */
  public function getSourceBasePath() {
    return $this->sourceBasePath;
  }

  /**
   * Set the source base path value.
   *
   * @param string $sourceBasePath
   *   Source base path.
   */
  protected function setSourceBasePath($sourceBasePath) {
    $this->sourceBasePath = $sourceBasePath;
  }

  /**
   * Return the source base URLs value.
   *
   * @return array|null
   *   Source base website URLs or null if not set.
   */
  public function getSourceBaseUrls() {
    return $this->sourceBaseUrls;
  }

  /**
   * Return the primary source base URL value.
   *
   * @return string|null
   *   Primary source base website URL or null if not set.
   */
  public function getPrimarySourceBaseUrl() {
    $sourceBaseUrls = $this->getSourceBaseUrls();

    // Return the first source base URL, which is considered the primary one.
    if (is_array($sourceBaseUrls)) {
      return reset($sourceBaseUrls);
    }

    return NULL;
  }

  /**
   * Set the source base website URLs value.
   *
   * @param array $sourceBaseUrls
   *   Source base website URLs.
   */
  protected function setSourceBaseUrls(array $sourceBaseUrls) {
    $this->sourceBaseUrls = $sourceBaseUrls;
  }

  /**
   * Return the target folder value.
   *
   * @return string|null
   *   Target folder or null if not set.
   */
  public function getTargetFolder() {
    return $this->targetFolder;
  }

  /**
   * Set the target folder value.
   *
   * @param string $targetFolder
   *   Target folder on destination.
   */
  protected function setTargetFolder($targetFolder) {
    $this->targetFolder = $targetFolder;
  }

  /**
   * Copy a file from the old platform to the new.
   *
   * @param string $source
   *   The source path (including the filename and relative to the files root of
   *   the old platform) of the file that needs to be copied.
   * @param string $target_folder
   *   The target directory URI where the file should be copied to.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity object or false if the file could not be copied.
   */
  protected function copyFile($source, $target_folder) {
    // For the copy process, we must omit eventual query parameters.
    // So strip off the query parameters first.
    $source = preg_replace('/\?.*/', '', $source);

    // Check if the file isn't already migrated.
    if ($existing = $this->loadFileFromMigrateMapping($source)) {
      return $existing;
    }

    // Construct the full file path to the source file.
    $source_name = drupal_basename($source);

    if (file_exists($source)) {
      // Make sure that the target folder exists and is writable.
      file_prepare_directory($target_folder, FILE_CREATE_DIRECTORY);

      // Create file object from a locally copied file.
      $target_path = $target_folder . '/' . $source_name;
      $file = File::Create([
        'uri' => $source,
      ]);
      $file = file_copy($file, $target_path, FILE_EXISTS_REPLACE);
    }
    else {
      // Make sure that the target folder exists and is writable.
      file_prepare_directory($target_folder, FILE_CREATE_DIRECTORY);

      $file = system_retrieve_file($source, $target_folder, TRUE);
    }

    if (!is_subclass_of($file, '\Drupal\file\FileInterface')) {
      $this->logMessage(
        t('Unable to copy file from source @source', [
          '@source' => $source,
        ]),
        MigrationInterface::MESSAGE_ERROR
      );
    }

    // HTML inline file migrate mapping.
    $this->saveMigrateMapping($source, $file);

    return $file;
  }

  /**
   * Create a file entity.
   *
   * @param string $data
   *   The data of the file that needs to be created.
   * @param string $target_file_path
   *   The target file path URI where the file should be saved to.
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FILE_EXISTS_REPLACE: Replace the existing file. If a managed file with
   *     the destination name exists, then its database entry will be updated.
   *     If no database entry is found, then a new one will be created.
   *   - FILE_EXISTS_RENAME: (default) Append _{incrementing number} until the
   *     filename is unique.
   *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity object or false if the file could not be created.
   */
  protected function createFile($data, $target_file_path, $replace = FILE_EXISTS_RENAME) {
    $file = FALSE;

    if (!empty($data)) {
      $file = file_save_data($data, $target_file_path, $replace);

      if ($file) {
        return $file;
      }
    }

    $this->logMessage(
      t('Unable to create file on target file path %target_file_path', [
        '%target_file_path' => $target_file_path,
      ]),
      MigrationInterface::MESSAGE_ERROR
    );

    return $file;
  }

  /**
   * Create a file entity.
   *
   * @param string $source
   *   The uri of the file that needs to be created, assuming that there is
   *   no need to copy actual file.
   * @param string $target_folder
   *   The target directory URI where the file should be copied to.
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FILE_EXISTS_REPLACE: Replace the existing file. If a managed file with
   *     the destination name exists, then its database entry will be updated.
   *     If no database entry is found, then a new one will be created.
   *   - FILE_EXISTS_RENAME: (default) Append _{incrementing number} until the
   *     filename is unique.
   *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity object or false if the file could not be created.
   */
  protected function createFileByUri($source, $target_folder, $replace = FILE_EXISTS_RENAME) {
    // Check if the file isn't already migrated.
    if ($existing = $this->loadFileFromMigrateMapping($source)) {
      if (is_subclass_of($existing, 'Drupal\file\FileInterface')) {
        return $existing;
      }
    }

    // Create file object from remote URL.
    try {
      // Using HTTP-client in case of using proxy server.
      $client = \Drupal::httpClient();
      $request = $client->get($source);
      $data = $request->getBody()->getContents();
      $file_name = drupal_basename($source);
      $file = $this->createFile($data, $target_folder . '/' . $file_name, $replace);
    }
    catch (\Exception $e) {
      $file = FALSE;
      $this->logMessage(
        t('Unable to create file from source @source', [
          '@source' => $source,
        ]),
        MigrationInterface::MESSAGE_ERROR
      );
    }

    // HTML inline file migrate mapping.
    $this->saveMigrateMapping($source, $file);

    return $file;
  }

  /**
   * Safely shorten the filename if too long.
   *
   * @param string $filename
   *   Filename we need to check.
   *
   * @return string
   *   The original if not too long or an MD5 hash of the original file.
   */
  protected function safeFilename($filename) {
    if (Unicode::strlen($filename) < 200) {
      return $filename;
    }

    $path_info = pathinfo($filename);
    $checksum = md5($path_info['filename']);
    $safe = $checksum . '.' . $path_info['extension'];
    return $safe;
  }

  /**
   * Load an already migrated file entity based on the source path.
   *
   * @param string $source
   *   The source path of the file to copy.
   *
   * @return \Drupal\file\FileInterface|false
   *   The migrated file entity object or false if not yet migrated.
   */
  protected function loadFileFromMigrateMapping($source) {
    $source = $this->safeFilename($source);

    $query = \Drupal::database()->select('migrate_html_to_paragraphs_map_inline_file', 'm')
      ->fields('m', ['fid'])
      ->condition('source_path', $source);
    $results = $query->execute();

    while ($record = $results->fetchObject()) {
      if (is_numeric($record->fid) && $file = File::load($record->fid)) {
        return $file;
      }
    }

    return FALSE;
  }

  /**
   * Save a migrated file to the mapping table.
   *
   * @param string $source
   *   The source path.
   * @param \Drupal\file\FileInterface|bool $file
   *   The file entity object or FALSE.
   */
  protected function saveMigrateMapping($source, $file) {
    $source = $this->safeFilename($source);
    $fid = is_subclass_of($file, 'Drupal\file\FileInterface') ? $file->id() : NULL;

    \Drupal::database()->merge('migrate_html_to_paragraphs_map_inline_file')
      ->key(['source_path' => $source])
      ->fields(['fid' => $fid])
      ->execute();
  }

}
