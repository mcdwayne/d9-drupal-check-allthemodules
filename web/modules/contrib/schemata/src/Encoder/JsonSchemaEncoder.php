<?php

namespace Drupal\schemata\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Drupal\jsonapi\Encoder\JsonEncoder as JsonApiEncoder;

/**
 * Encodes JSON API data.
 *
 * Simply respond to application/vnd.api+json format requests using encoder.
 */
class JsonSchemaEncoder extends JsonApiEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected $outerFormat = 'schema_json';

  /**
   * The decorated encoder.
   *
   * @var \Symfony\Component\Serializer\Encoder\EncoderInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface
   */
  protected $inner;

  /**
   * @param \Symfony\Component\Serializer\Encoder\EncoderInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface $inner
   */
  public function setInnerEncoder(EncoderInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $this->inner->supportsEncoding($format) || strpos($format, "{$this->outerFormat}:") === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $this->inner->supportsDecoding($format);
  }

}
