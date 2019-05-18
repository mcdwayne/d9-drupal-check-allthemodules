<?php

namespace Drupal\exif;

use Drupal;

/**
 * Class SimpleExifToolFacade.
 *
 * @package Drupal\exif
 */
class SimpleExifToolFacade implements ExifInterface {

  static private $instance = NULL;

  /**
   * We are implementing a singleton pattern.
   */
  private function __construct() {
  }

  /**
   * Return singleton instance.
   *
   * @return \Drupal\exif\ExifInterface
   *   the chosen implementation instance.
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Check the exiftool is available.
   *
   * @return bool
   *   TRUE if available.
   */
  public static function checkConfiguration() {
    $exiftoolLocation = self::getExecutable();
    return isset($exiftoolLocation) && is_executable($exiftoolLocation);
  }

  /**
   * Return the 'exiftool' location stored in exif settings.
   *
   * @return string
   *   path to 'exiftool'.
   */
  private static function getExecutable() {
    $config = Drupal::configFactory()->get('exif.settings');
    return $config->get('exiftool_location');
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataFields(array $arCckFields = []) {
    foreach ($arCckFields as $drupal_field => $metadata_settings) {
      $metadata_field = $metadata_settings['metadata_field'];
      $ar = explode("_", $metadata_field);
      if (isset($ar[0])) {
        $section = $ar[0];
        unset($ar[0]);
        $arCckFields[$drupal_field]['metadata_field'] = [
          'section' => $section,
          'tag' => implode("_", $ar),
        ];
      }
    }
    return $arCckFields;
  }

  /**
   * {@inheritdoc}
   */
  public function readMetadataTags($file, $enable_sections = TRUE) {
    if (!file_exists($file)) {
      return [];
    }
    $data = $this->readAllInformation($file, $enable_sections);
    return $data;
  }

  /**
   * Retrieve all metadata using exifTool.
   *
   * @param string $file
   *   Image to scan.
   * @param bool $enable_sections
   *   Extract sections or not.
   * @param bool $enable_markerNote
   *   Extract marker notes or not (for now, always FALSE)
   * @param bool $enable_non_supported_tags
   *   Extract non supported tags or not (for now, always FALSE)
   *
   * @return array
   *   all metadata usable by this module.
   */
  private function readAllInformation($file, $enable_sections = TRUE, $enable_markerNote = FALSE, $enable_non_supported_tags = FALSE) {
    $jsonAsString = $this->runTool($file, $enable_sections, $enable_markerNote, $enable_non_supported_tags);
    $json = json_decode($jsonAsString, TRUE);
    $errorCode = json_last_error();
    if ($errorCode == JSON_ERROR_NONE) {
      return $this->toLowerJsonResult($json[0]);
    }
    else {
      $errorMessage = "";
      switch ($errorCode) {
        case JSON_ERROR_DEPTH:
          $errorMessage = 'Maximum stack depth exceeded';
          break;

        case JSON_ERROR_STATE_MISMATCH:
          $errorMessage = 'Underflow or the modes mismatch';
          break;

        case JSON_ERROR_CTRL_CHAR:
          $errorMessage = 'Unexpected control character found';
          break;

        case JSON_ERROR_SYNTAX:
          $errorMessage = 'Syntax error, malformed JSON';
          break;

        case JSON_ERROR_UTF8:
          $errorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
          break;

        default:
          $errorMessage = 'Unknown error';
          break;
      }
      // Logs a notice.
      \Drupal::logger('exif')->notice(t($errorMessage));
      return [];
    }
  }

  /**
   * Handle how to call exiftool.
   *
   * @param string $file
   *   Image to scan.
   * @param bool $enable_sections
   *   Extract sections or not.
   * @param bool $enable_markerNote
   *   Extract marker notes or not (for now, always FALSE)
   * @param bool $enable_non_supported_tags
   *   Extract non supported tags or not (for now, always FALSE)
   *
   * @return string
   *   ExifTool JSON result containing all metadata.
   */
  private function runTool($file, $enable_sections = TRUE, $enable_markerNote = FALSE, $enable_non_supported_tags = FALSE) {
    $params = "";
    if ($enable_sections) {
      $params = "-g -struct ";
    }
    if ($enable_markerNote) {
      $params = $params . "-fast ";
    }
    else {
      $params = $params . "-fast2 ";
    }
    if ($enable_non_supported_tags) {
      $params = $params . " -u -U";
    }
    $commandline = self::getExecutable() . " -E -n -json " . $params . "\"" . $file . "\"";
    $output = [];
    $returnCode = 0;
    exec($commandline, $output, $returnCode);
    if ($returnCode != 0) {
      $output = "";
      Drupal::logger('exif')
        ->warning(t("exiftool return an error. can not extract metadata from file :file", [':file' => $file]));
    }
    $info = implode("\n", $output);
    return $info;
  }

  /**
   * Translate all keys to lowercase.
   *
   * ExiffTool is case sensitive. the module is not.
   * So we need to lowercase all keys to be able to
   * get the needed values.
   *
   * @param array $data
   *   Values by keys.
   *
   * @return array
   *   same values with lowercase keys.
   */
  private function toLowerJsonResult(array $data) {
    $result = [];
    foreach ($data as $section => $values) {
      if (is_array($values)) {
        $result[strtolower($section)] = array_change_key_case($values);
      }
      else {
        $result[strtolower($section)] = $values;
      }

    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldKeys() {
    return [];
  }

}
