<?php
/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\Field\FieldType\FileCompressorFieldItemListInterface.
 */
namespace Drupal\file_compressor_field\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 * Represents a configurable entity file compressor field.
 */
interface FileCompressorFieldItemListInterface extends EntityReferenceFieldItemListInterface {

  /**
   * Determines the URI for a file field.
   *
   * @param $data
   *   An array of token objects to pass to token_replace().
   *
   * @return
   *   A file directory URI with tokens replaced.
   *
   * @see token_replace()
   */
  public function getUploadLocation($data = array());
}