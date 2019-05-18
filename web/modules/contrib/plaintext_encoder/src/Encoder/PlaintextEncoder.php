<?php

namespace Drupal\plaintext_encoder\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Drupal\Component\Utility\Html;

/**
 * Adds Plaintext encoder support for the views_data_export.
 */
class PlaintextEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'plaintext';

  /**
   * Constructs the class.
   */
  public function __construct($trim_values = TRUE) {
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public function encode($data, $format, array $context = []) {
    switch (gettype($data)) {
      case "array":
        break;

      case 'object':
        $data = (array) $data;
        break;

      // May be bool, integer, double, string, resource, NULL, or unknown.
      default:
        $data = [$data];
        break;
    }

    // Set data.
    $output = '';
    foreach ($data as $row) {
      $output .= $this->formatRow($row);
    }

    return trim($output);
  }

  /**
   * Moved structures into a string, and formats the string.
   */
  public function formatRow($row) {
    $formatted_row = [];

    foreach ($row as $data) {
      if (is_array($data)) {
        $value = $this->array2str($data);
      }
      else {
        $value = $data;
      }

      $formatted_row[] = $this->formatValue($value);
    }

    return implode("\r\n", $formatted_row);
  }

  /**
   * Moved multi-dimensional array into a single level.
   */
  protected function array2str($data) {
    $depth = $this->arrayDepth($data);

    if ($depth == 1) {
      return implode("\r\n", $data);
    }
    else {
      $value = "";
      foreach ($data as $item) {
        $value .= "\r\n" . $this->array2str($item);
      }
      return trim($value);
    }
  }

  /**
   * Formats to plaintext.
   *
   * @param string $value
   *   The raw value to be formatted.
   *
   * @return string
   *   The formatted value.
   */
  protected function formatValue($value) {
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    $value = trim($value);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    $results = [];
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return static::$format;
  }

  /**
   * Determine the depth of an array.
   *
   * This method determines array depth by analyzing the indentation of the
   * dumped array. This avoid potential issues with recursion.
   *
   * @see http://stackoverflow.com/a/263621
   */
  protected function arrayDepth($array) {
    $max_indentation = 1;

    $array_str = print_r($array, TRUE);
    $lines = explode("\n", $array_str);

    foreach ($lines as $line) {
      $indentation = (strlen($line) - strlen(ltrim($line))) / 4;

      if ($indentation > $max_indentation) {
        $max_indentation = $indentation;
      }
    }

    return ceil(($max_indentation - 1) / 2) + 1;
  }

}
