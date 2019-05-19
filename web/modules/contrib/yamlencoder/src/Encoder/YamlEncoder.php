<?php

namespace Drupal\yamlencoder\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * Adds YAML support for serializer.
 */
class YamlEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('yaml');

  /**
   * A shared YAML dumper instance.
   *
   * @var \Symfony\Component\Yaml\Dumper
   */
  protected $dumper;

  /**
   * A shared YAML parser instance.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * Implements \Symfony\Component\Serializer\Encoder\EncoderInterface::encode().
   */
  public function encode($data, $format, array $context = array()){
    return $this->getDumper()->dump($data, PHP_INT_MAX);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsEncoding().
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\EncoderInterface::decode().
   */
  public function decode($data, $format, array $context = array()){
    return $this->getParser()->parse($data);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsDecoding().
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * Gets the YAML dumper instance.
   *
   * @return \Symfony\Component\Yaml\Dumper
   */
  protected function getDumper() {
    if (!isset($this->dumper)) {
      $this->dumper = new Dumper();
      // Set Yaml\Dumper's default indentation for nested nodes/collections to
      // 2 spaces for consistency with Drupal coding standards.
      $this->dumper->setIndentation(2);
    }

    return $this->dumper;
  }

  /**
   * Gets the YAML parser instance.
   *
   * @return \Symfony\Component\Yaml\Parser
   */
  protected function getParser() {
    if (!isset($this->parser)) {
      $this->parser = new Parser();
    }

    return $this->parser;
  }

}