<?php

/**
 * @file
 * Contains \Drupal\pgn\Serializer\Encoder\XmlEncoder.
 */

namespace Drupal\pgn\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;

/**
 * Adds XML support for serializer.
 *
 * This acts as a wrapper class for Symfony's XmlEncoder so that it is not
 * implementing NormalizationAwareInterface, and can be normalized externally.
 */
class XmlEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('pgnxml');

  /**
   * An instance of the Symfony XmlEncoder to perform the actual encoding.
   *
   * @var \Symfony\Component\Serializer\Encoder\XmlEncoder
   */
  protected $baseEncoder;

  /**
   * Constructs the XmlEncoder object, creating a BaseXmlEncoder also if needed.
   */
  public function __construct(BaseXmlEncoder $base_encoder = NULL) {
    $this->baseEncoder = $base_encoder === NULL ? new BaseXmlEncoder() : $base_encoder;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = array()){
    switch ($format) {
      case 'pgnxml':
        $context['xml_root_node_name'] = 'PGNGAMES';
        return $this->baseEncoder->encode($data, 'xml', $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()){
    return $this->baseEncoder->decode($data, $format, $context) + array('@xmlns' => 'x-schema:pgn.xdr');
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }
}
