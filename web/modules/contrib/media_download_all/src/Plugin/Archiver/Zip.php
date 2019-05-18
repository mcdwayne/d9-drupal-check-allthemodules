<?php

namespace Drupal\media_download_all\Plugin\Archiver;

use Drupal\Core\Archiver\Zip as BaseZip;
use Drupal\Core\Archiver\ArchiverException;

/**
 * Defines an archiver implementation for .zip files.
 *
 * @Archiver(
 *   id = "media_download_all_files_zip_archiver",
 *   title = @Translation("Media download all files zip archiver"),
 *   description = @Translation("Handles zip files for media download all files."),
 *   extensions = {"zip"}
 * )
 */
class Zip extends BaseZip {

  /**
   * Flysystem Aliyun OSS schemas.
   *
   * @var array
   */
  private $aliyunOssSchemas = [];

  /**
   * A file storage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fileStorage;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct($file_path, $zip_append = FALSE) {
    $this->zip = new \ZipArchive();

    if (file_exists($file_path)) {
      if ($zip_append) {
        if ($this->zip->open($file_path) !== TRUE) {
          throw new ArchiverException(t('Cannot open %file_path', ['%file_path' => $file_path]));
        }
      }
      elseif ($this->zip->open($file_path, \ZipArchive::OVERWRITE) !== TRUE) {
        throw new ArchiverException(t('Cannot open %file_path', ['%file_path' => $file_path]));
      }
    }
    else {
      if ($this->zip->open($file_path, \ZipArchive::CREATE) !== TRUE) {
        throw new ArchiverException(t('Cannot open %file_path', ['%file_path' => $file_path]));
      }
    }

    $this->aliyunOssSchemas = $this->getAliyunOssSchemas();
    $this->fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    $this->fileSystem = \Drupal::service('file_system');
  }

  /**
   * {@inheritdoc}
   */
  public function add($fid) {
    /* @var \Drupal\file\Entity\File $file */
    $file = $this->fileStorage->load($fid);
    $file_name = $fid . ' - ' . $file->label();
    $uri = $file->getFileUri();
    if ($this->isAliyunOssSchema($uri)) {
      $url = file_create_url($uri);
      $this->zip->addFromString($file_name, file_get_contents($url));
      return $this;
    }
    $realpath = $this->fileSystem->realpath($uri);
    $this->zip->addFile($realpath, $file_name);
    return $this;
  }

  /**
   * Method to close the opened archive file.
   */
  public function close() {
    $this->zip->close();
  }

  /**
   * Get aliyun_oss schemas.
   *
   * @return array
   *   The schemas using aliyun_oss as driver in settings.php.
   */
  private function getAliyunOssSchemas() {

    /* @var \Drupal\Core\Site\Settings $settings */
    $settings = \Drupal::service('settings');

    $flysystem_schemas = $settings->get('flysystem', NULL);

    $aliyun_oss_schemas = [];
    if ($flysystem_schemas !== NULL) {
      foreach ($flysystem_schemas as $schema => $config) {
        if ($config['driver'] === 'aliyun_oss') {
          $aliyun_oss_schemas[] = $schema;
        }
      }

    }
    return $aliyun_oss_schemas;
  }

  /**
   * Get schema from URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @return bool|string
   *   The schema or FALSE.
   */
  private function getSchema($uri) {
    /* @var \Drupal\Core\File\FileSystemInterface $filesystem */
    $filesystem = \Drupal::service('file_system');
    return $filesystem->uriScheme($uri);
  }

  /**
   * Check whether the uri is provided by the Flysystem Aliyun OSS module.
   *
   * @param string $uri
   *   The uri to be check.
   *
   * @return bool
   *   The result.
   */
  private function isAliyunOssSchema($uri) {
    if (empty($this->aliyunOssSchemas)) {
      return FALSE;
    }
    $schema = $this->getSchema($uri);
    return is_string($schema) && in_array($schema, $this->aliyunOssSchemas, TRUE);
  }

}
