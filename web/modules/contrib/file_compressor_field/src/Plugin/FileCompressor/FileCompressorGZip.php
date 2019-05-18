<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\FileCompressor\FileCompressorGZip.
 */

namespace Drupal\file_compressor_field\Plugin\FileCompressor;

use Drupal\file_compressor_field\Plugin\FileCompressorBase;

/**
 * Implements GZip compressor for File Compressor field.
 *
 * @FileCompressor(
 *   id = "gzip_zlib",
 *   admin_label = @Translation("GZip (Zlib)"),
 *   extension = "tar.gz"
 * )
 */
class FileCompressorGZip extends FileCompressorBase {

  /**
   * @{@inheritdoc}
   */
  public function generateCompressedFile($file_uri, $files) {
    $full_file_uri = drupal_realpath($file_uri);
    $tar = new \Archive_Tar($full_file_uri, 'gz');
    $tmp = 'temporary://file_compressor_field' . time() . user_password();
    drupal_mkdir($tmp);
    foreach ($files as $file) {
      file_unmanaged_copy($file, $tmp, FILE_EXISTS_REPLACE);
    }
    $tar->createModify($tmp, '', $tmp);
    file_unmanaged_delete_recursive($tmp);

    return TRUE;
  }

}
