<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Web Service Encoder plugins.
 */
abstract class WSEncoderBase extends PluginBase implements WSEncoderInterface {

  /**
   * Encode data into format just sending it off.
   */
  abstract public function encode(&$data, &$replacement, &$url);

}
