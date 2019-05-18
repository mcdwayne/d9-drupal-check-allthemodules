<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\FileCompressorPluginInterface.
 */

namespace Drupal\file_compressor_field\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for File Compressor backends.
 *
 * Modules implementing this interface may want to extend FileCompressorBase
 * class, which provides default implementations of each method.
 *
 * @see \Drupal\file_compressor_field\Annotation\FileCompressor
 * @see \Drupal\file_compressor_field\Plugin\FileCompressorBase
 * @see \Drupal\file_compressor_field\Plugin\FileCompressorManager
 * @see plugin_api
 */
interface FileCompressorPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the file extension for this plugin.
   *
   * @return string
   *   The expected file extension for this plugin.
   */
  public function getExtension();

  /**
   * Returns the complete file uri given a base uri.
   *
   * @param string $base_uri
   *   The base uri for the compressed file.
   *
   * @return string
   *   The final uri for the compressed file.
   */
  public function generateCompressedFileUri($base_uri);

  /**
   * Generates a compressed file given the uri to store and files to archive.
   *
   * @param $file_uri
   *   URI to store the compressed file.
   * @param array $files
   *   Files to archive.
   *
   * @return bool
   *   Boolean indicating if the file has been created properly or not.
   */
  public function generateCompressedFile($file_uri, $files);
  
}
