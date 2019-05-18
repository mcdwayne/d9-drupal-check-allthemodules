<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\FileCompressorBase.
 */

namespace Drupal\file_compressor_field\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base class from which other modules providing File Compressor
 * backends may extend.
 *
 * A complete sample plugin definition should be defined as in this example:
 *
 * @code
 * @FileCompressor(
 *   id = "file_compressor_backend_default",
 *   admin_label = @Translation("Default Backend"),
 *   extension = "zip"
 * )
 * @endcode
 *
 * @see \Drupal\file_compressor_field\Annotation\FileCompressor
 * @see \Drupal\file_compressor_field\Plugin\FileCompressorPluginInterface
 * @see \Drupal\file_compressor_field\Plugin\FileCompressorManager
 * @see plugin_api
 */
abstract class FileCompressorBase extends PluginBase implements FileCompressorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    $definition = $this->getPluginDefinition();
    return $definition['extension'];
  }

  /**
   * @{@inheritdoc}
   */
  public function generateCompressedFileUri($base_uri) {
    return $base_uri . '.' . $this->getExtension();
  }

}
