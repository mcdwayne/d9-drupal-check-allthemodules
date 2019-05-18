<?php

namespace Drupal\couchbasedrupal;

interface TranscoderInterface {

  /**
   * The value to encode.
   *
   * This function must return a array in the form of
   * [$encoded, $flags, $datatype] where $flags
   * and $datatype are metadata that is sent to
   * decode() when trying to retrieve the data.
   *
   * @param mixed $value
   */
  function encode($value);

  /**
   * Function used to decode data

   * @param string $bytes 

   * @param int $flags 

   * @param int $datatype 
   */
  function decode(string $bytes, int $flags, int $datatype);

}
