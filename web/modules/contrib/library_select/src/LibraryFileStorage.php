<?php

namespace Drupal\library_select;

use Drupal\library_select\Entity\LibrarySelectEntity;

/**
 * Class AssetFileStorage.
 *
 * @package Drupal\library_select
 *
 * This class borrow from asset_injector.
 * This asset file storage class implements a content-addressed file system
 * where each file is stored in a location like so:
 * public://library_select/[extension]/[name]-[md5].[extension]
 * Note that the name and extension-dir are redundant and purely for DX.
 */
final class LibraryFileStorage {

  /**
   * Asset with file storage.
   *
   * @var \Drupal\library_select\Entity\LibrarySelectEntity
   */
  protected $asset;

  /**
   * LibraryFileStorage constructor.
   *
   * @param \Drupal\library_select\Entity\LibrarySelectEntity $asset
   *   The library asset.
   */
  public function __construct(LibrarySelectEntity $asset) {
    $this->asset = $asset;
  }

  /**
   * Create file and return internal uri.
   *
   * @param string $type
   *   The type.
   *
   * @return string
   *   Internal file URI using public:// stream wrapper.
   */
  public function createFile($type) {
    $internal_uri = self::internalFileUri($type);
    if (!is_file($internal_uri)) {
      $directory = dirname($internal_uri);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      file_unmanaged_save_data($this->asset->getCode($type), $internal_uri, FILE_EXISTS_REPLACE);
    }
    return $internal_uri;
  }

  /**
   * Delete files for an asset.
   *
   * Yes, we can have multiple files for an asset configuration, if we have
   * overrides.
   */
  public function deleteFiles() {
    $this->deleteFilesType($this->asset->cssExtension);
    $this->deleteFilesType($this->asset->jsExtension);
  }

  /**
   * Delete files for an asset.
   *
   * @param string $type
   *   The extension.
   */
  private function deleteFilesType($type) {
    $pattern = $this->internalFileUri($type, TRUE);
    $paths = glob($pattern);
    foreach ($paths as $path) {
      file_unmanaged_delete($path);
    }
  }

  /**
   * Create internal file URI or pattern.
   *
   * @param string $type
   *   The code type.
   * @param bool $pattern
   *   Get Pattern instead of internal file URI.
   *
   * @return string
   *   File uri.
   */
  protected function internalFileUri($type, $pattern = FALSE) {
    $name = $this->asset->id();
    $extension = $type;
    $hash = $pattern ? '*' : md5($this->asset->getCode($type));
    $all_assets_directory = self::internalDirectoryUri();
    if ($pattern) {
      // glob() does not understand stream wrappers. Sigh.
      $all_assets_directory = \Drupal::service('file_system')
        ->realpath($all_assets_directory);
    }
    $internal_uri = "$all_assets_directory/$extension/$name-$hash.$extension";
    return $internal_uri;
  }

  /**
   * Get our directory.
   *
   * @return string
   *   Directory of the assets.
   */
  protected static function internalDirectoryUri() {
    return 'public://library-select';
  }

  /**
   * Delete all asset files.
   *
   * @see asset_injector_cache_flush()
   */
  public static function deleteAllFiles() {
    file_unmanaged_delete_recursive(self::internalDirectoryUri());
  }

}
