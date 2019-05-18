<?php

namespace Drupal\apitools\Utility;

class ArrayIterator extends \ArrayIterator {

  /**
   * @param array $array
   * @param $key
   * @param $value
   */
  public static function getOptionsList(array $array, $key, $value) {
    return (new static($array))->toOptionsList($key, $value);
  }

  /**
   * @param $key
   * @param $value
   *
   * @return mixed
   */
  public function toOptionsList($key, $value) {
    return array_reduce($this->getArrayCopy(), function($carry, $item) use ($key, $value) {
      $carry[$item[$key]] = $item[$value];
      return $carry;
    }, []);
  }
}
