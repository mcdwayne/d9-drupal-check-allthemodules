<?php

namespace Drupal\aes\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for all cryptor plugins.
 */
abstract class AESPluginBase extends PluginBase {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @param string $data
   *   Data to be encoded.
   * @param bool|string $key
   *   Optional key to be used when encoding.
   * @param bool|string $cipher
   *   Optional cipher to be used when encoding. If present, contain one of the following strings:
   *   rijndael-128, rijndael-192, rijndael-256
   *
   * @return mixed encoded data.
   */
  abstract public function encrypt($data, $key = FALSE, $cipher = FALSE);

  /**
   * @param string $data
   *   Data to be decoded.
   * @param bool|string $key
   *   Optional key to be used for decoding.
   * @param bool|string $cipher
   *   Optional cipher to be used for decoding. If present, contain one of the following strings:
   *   rijndael-128, rijndael-192, rijndael-256
   *
   * @return string decoded string.
   */
  abstract public function decrypt($data, $key = FALSE, $cipher = FALSE);

}
