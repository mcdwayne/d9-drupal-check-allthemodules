<?php

namespace Drupal\couchbasedrupal;

class TranscoderPassThru implements TranscoderInterface {

  /**
   * Default passthru encoder which simply passes data
   * as-is rather than performing any transcoding.
   *
   * @internal
   */
  function encode($value) : array {
    return [$value, 0, 0];
  }

  /**
   * Pass-through encoder that does nothing
   * to the document.
   *
   * @param string $bytes 
   * @param int $flags 
   * @param int $datatype 
   * @return string
   */
  function decode(string $bytes, int $flags, int $datatype) {
    return $bytes;
  }
}
