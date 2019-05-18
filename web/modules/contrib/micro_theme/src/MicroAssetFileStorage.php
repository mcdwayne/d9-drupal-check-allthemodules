<?php

namespace Drupal\micro_theme;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class AssetFileStorage.
 *
 * @package Drupal\micro_theme
 *
 * This asset file storage class implements a content-addressed file system
 * where each file is stored in a location like so:
 * public://asset/[extension]/[name]-[md5].[extension]
 * Note that the name and extension-dir are redundant and purely for DX.
 *
 * Due to the nature of the config override system, the content of any asset
 * config entity can vary on external factory beyond our control, be it
 * language, domain, settings.php overrides or anything else. In other words,
 * any asset entity can map to an arbitrary number of actual assets.
 * Thus asset files are generated in MicroAssetFileStorage::internalFileUri()
 * with a file name that is unique by their content, and only deleted on cache
 * flush.
 *
 * Class inspired from asset_injector module.
 *
 * Also see comments on caching in @see micro_theme_page_attachments().
 */
abstract class MicroAssetFileStorage {

  /**
   * The type of asset (color, font, etc).
   *
   * @var string
   */
  protected $type;

  /**
   * The path to the file model.
   *
   * @var string
   */
  protected $fileModel;

  /**
   * The array of pattern and value to replace.
   *
   * @var array
   */
  protected $replacePattern;

  /**
   * The micro site id.
   *
   * @var int
   */
  protected $siteId;

  /**
   * AssetFileStorage constructor.
   *
   * @param $type
   * @param $file_model
   * @param $replace_pattern
   * @param int $site_id
   */
  public function __construct($type, $file_model, $replace_pattern, $site_id) {
    $this->fileModel = $file_model;
    $this->replacePattern = $replace_pattern;
    $this->type = $type;
    $this->siteId = $site_id;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function extension();

  /**
   * Create file and return internal uri.
   *
   * @return string
   *   Internal file URI using public:// stream wrapper.
   */
  public function createFile() {
    $internal_uri = self::internalFileUri();
    if (!is_file($internal_uri)) {
      $directory = dirname($internal_uri);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      file_unmanaged_save_data($this->getCss(), $internal_uri, FILE_EXISTS_REPLACE);
    }
    return $internal_uri;
  }

  /**
   * Update file and return internal uri.
   *
   * @return string
   *   Internal file URI using public:// stream wrapper.
   */
  public function updateFile() {
    $internal_uri = $this->createFile();
    file_unmanaged_save_data($this->getCss(), $internal_uri, FILE_EXISTS_REPLACE);
    return $internal_uri;
  }

  /**
   * Delete files for an asset.
   *
   * Yes, we can have multiple files for an asset configuration, if we have
   * overrides.
   */
  public function deleteFiles() {
    $pattern = $this->internalFileUri(TRUE);
    $paths = glob($pattern);
    foreach ($paths as $path) {
      file_unmanaged_delete($path);
    }
  }

  public function getCss() {
    if (!is_file($this->fileModel)) {
      return '';
    }
    $file_model = file_get_contents($this->fileModel);
    $search = array_keys($this->replacePattern);
    $replace = array_values($this->replacePattern);
    $asset = str_replace($search, $replace, $file_model);
    return $asset;
  }

  /**
   * Create internal file URI or pattern.
   *
   * @param bool $pattern
   *   Get Pattern instead of internal file URI.
   *
   * @return string
   *   File uri.
   */
  protected function internalFileUri($pattern = FALSE) {
    $type = $this->type;
    $extension = $this->extension();
    $site = $this->siteId;
    $name = '';
    foreach ($this->replacePattern as $value) {
      $name = $name . $value;
    }
    $hash = $pattern ? '*' : md5($name);
    $all_assets_directory = self::internalDirectoryUri();
    if ($pattern) {
      // glob() does not understand stream wrappers. Sigh.
      $all_assets_directory = \Drupal::service('file_system')
        ->realpath($all_assets_directory);
    }

    $internal_uri = "$all_assets_directory/$site/$type/$type-$hash.$extension";
    return $internal_uri;
  }

  /**
   * Get our directory.
   *
   * @return string
   *   Directory of the assets.
   */
  protected static function internalDirectoryUri() {
    return 'public://micro_theme_asset';
  }

  /**
   * Delete all asset files.
   */
  public static function deleteAllFiles() {
    file_unmanaged_delete_recursive(self::internalDirectoryUri());
  }

  /**
   * Delete all asset files related to a micro site.
   *
   * @param int $site_id
   *   The micro site ID.
   */
  public static function deleteAllSiteFiles($site_id) {
    $directory = self::internalDirectoryUri() . '/' . $site_id;
    file_unmanaged_delete_recursive($directory);
  }

}
