<?php

namespace Drupal\exif;

/**
 * Interface ExifInterface define contract for implementations.
 *
 * @package Drupal\exif
 */
interface ExifInterface {

  /**
   * Return drupal fields related to this extension.
   *
   * Going through all the fields that have been created for a given node type
   * and try to figure out which match the naming convention -> so that we know
   * which exif information we have to read.
   *
   * Naming convention are: field_exif_xxx (xxx would be the name of the exif
   * tag to read.
   *
   * @param array $arCckFields
   *   CCK fields.
   *
   * @return array
   *   List of exif tags to read for this image
   */
  public function getMetadataFields(array $arCckFields = []);

  /**
   * Retrieve all metadata from a file.
   *
   * @param string $file
   *   File to read metadata from.
   * @param bool $enable_sections
   *   Should sections should be also retrieved.
   *
   * @return array
   *   metadata keys and associated values.
   */
  public function readMetadataTags($file, $enable_sections = TRUE);

  /**
   * Get all supported keys.
   *
   * @return array
   *   Keys by sections.
   */
  public function getFieldKeys();

}
