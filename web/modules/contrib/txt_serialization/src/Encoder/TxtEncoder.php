<?php

namespace Drupal\txt_serialization\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Adds TXT encoder support for the Serialization API.
 */
class TxtEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'txt';

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

    $dataType = gettype($data);

    if ($dataType != 'string') {
      throw new InvalidDataTypeException('Invalid data type "' . $dataType . '"', '400', '');
    }

    return $this->formatValue($data);

  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    return $this->formatValue($data);
  }

  /**
   * Formats value for a given data.
   */
  protected function formatValue($value) {
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    $value = trim($value);

    return $value;
  }

}
