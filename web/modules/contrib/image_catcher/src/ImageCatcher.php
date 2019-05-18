<?php

namespace Drupal\image_catcher;

use Drupal\Core\Utility\Token;
use Drupal\pathauto\AliasCleaner;

/**
 * Image Catcher creates images files from external source.
 */
class ImageCatcher implements ImageCatcherInterface {

  /**
   * Token manager injection.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenManager;

  public static $supportedExtensions = ['gif', 'jpeg', 'jpg', 'png'];

  /**
   * {@inheritdoc}
   */
  public function __construct(Token $token_manager, AliasCleaner $aliascleaner) {
    $this->tokenManager = $token_manager;
    $this->aliascleaner = $aliascleaner;
  }

  /**
   * {@inheritdoc}
   */
  public function createFromBase64(string $image_base64, string $dir_name, string $image_name) {
    try {
      list($mime_type, $base64_content) = explode(',', $image_base64);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $pattern = "/image\/([^;]+);/";
    $matches = [];

    $extension = preg_match($pattern, $mime_type, $matches)
      ? $matches[1]
      : '';

    if (!in_array($extension, self::$supportedExtensions)) {
      return FALSE;
    }

    $data = base64_decode($base64_content);

    if (!$data) {
      return FALSE;
    }

    $image_name = $this->aliascleaner->cleanString($image_name);
    $filename = "$image_name.$extension";

    return $this->createFile($data, $dir_name, $filename);
  }

  /**
   * {@inheritdoc}
   */
  public function createFromUrl(string $image_url, string $dir_name) {
    $path_infos = pathinfo($image_url);
    $data = file_get_contents($image_url);

    if (!in_array($path_infos['extension'], self::$supportedExtensions)) {
      return FALSE;
    }

    $filename = "{$path_infos['filename']}.{$path_infos['extension']}";

    return $this->createFile($data, $dir_name, $filename);
  }

  /**
   * Helper - Create a file from given data.
   *
   * @param string $data
   *   Either a base64 content or the result of a file_get_contents().
   * @param string $dir_name
   *   The directory name in which we want to save the image.
   * @param string $filename
   *   The name of the file including the extension.
   *
   * @return int|bool
   *   File id if the file has successfully been created, else FALSE.
   */
  public function createFile(string $data, string $dir_name, string $filename) {
    $dir_name = $this->tokenManager->replace($dir_name);
    $destination_folder = file_default_scheme() . '://' . $dir_name;
    file_prepare_directory($destination_folder, FILE_CREATE_DIRECTORY);

    $destination = file_create_filename($filename, $destination_folder);

    $file = file_save_data($data,
      $destination,
      FILE_EXISTS_REPLACE
    );

    if ($file) {
      $file->setPermanent();
      $file->save();
      return $file->id();
    }
    return FALSE;
  }

}
