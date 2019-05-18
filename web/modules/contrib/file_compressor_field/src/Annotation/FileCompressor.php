<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Annotation\FileCompressor.
 */

namespace Drupal\file_compressor_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FileCompressor annotation object.
 *
 * @ingroup file_compressor_field_api
 *
 * @Annotation
 */
class FileCompressor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the FileCompressor backend.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

  /**
   * The file extension provided of the FileCompressor backend.
   *
   * @var string
   */
  public $extension;

}
