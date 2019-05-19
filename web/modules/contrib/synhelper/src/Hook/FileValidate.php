<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * FileValidate.
 */
class FileValidate extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook($file) {
    $destination = self::transliteration($file->destination);
    $basename = drupal_basename($destination);
    $directory = drupal_dirname($destination);
    $file->destination = file_create_filename($basename, $directory);
  }

  /**
   * Transliteration.
   */
  public static function transliteration($str) {
    $str = str_replace(' ', '_', $str);
    $str = \Drupal::transliteration()->transliterate($str);
    $str = strtolower($str);
    return $str;
  }

}
