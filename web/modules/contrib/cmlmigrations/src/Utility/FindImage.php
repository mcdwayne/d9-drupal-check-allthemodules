<?php

namespace Drupal\cmlmigrations\Utility;

/**
 * FindImage.
 */
class FindImage {

  /**
   * Find images.
   */
  public static function getBy1cImage($image = 'import_files', $all = TRUE) {
    $images = [];

    if ($image || $all) {
      $query = \Drupal::database()->select('file_managed', 'files')
        ->fields('files', [
          'fid',
          'uri',
        ])
        ->condition('uri', "%$image%", 'LIKE');
      $res = $query->execute();

      if ($res) {
        foreach ($res as $file) {
          $uri = strstr($file->uri, 'import_files');
          $fid = $file->fid;
          $images[$uri] = $fid;
        }
      }
    }
    return $images;
  }

}
