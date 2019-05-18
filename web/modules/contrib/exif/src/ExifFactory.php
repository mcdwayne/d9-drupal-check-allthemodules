<?php

namespace Drupal\exif;

use Drupal;

/**
 * Class ExifFactory allow to get right implementation.
 *
 * @package Drupal\exif
 */
class ExifFactory {

  /**
   * Return description of exif parser implementations.
   *
   * @return array
   *   list names of parser implementations
   */
  public static function getExtractionSolutions() {
    return [
      "simple_exiftool" => "exiftool",
      "php_extensions" => "php extensions",
    ];
  }

  /**
   * Return configured exif parser.
   *
   * @return \Drupal\exif\ExifInterface
   *   configured exif parser
   */
  public static function getExifInterface() {
    $config = Drupal::configFactory()->get('exif.settings');
    $extractionSolution = $config->get('extraction_solution');
    $useExifToolSimple = $extractionSolution == "simple_exiftool";
    if (isset($useExifToolSimple) && $useExifToolSimple && SimpleExifToolFacade::checkConfiguration()) {
      return SimpleExifToolFacade::getInstance();
    }
    else {
      // Default case for now (same behavior as previous versions)
      return ExifPHPExtension::getInstance();
    }
  }

}
