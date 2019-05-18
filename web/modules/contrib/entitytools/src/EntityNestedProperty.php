<?php

namespace Drupal\entitytools;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\TypedDataInterface;

class EntityNestedProperty {

  protected $current;

  public function __construct($current) {
    $this->current = $current;
  }

  public static function create($current) {
    return new static($current);
  }

  public function getNestedArray($path, $default = NULL) {
    // Note that toArray has keys that are not equal to properties.
    // While an entityref has property key "entity", in array it's "subform".
    if ($this->current) {
      $value = NestedArray::getValue($this->current->toArray(), explode('/', $path), $keyExists);
      return $keyExists ? $value : $default;
    }
    else {
      return $default;
    }
  }

  public function getNestedValue($path, $default = NULL) {
    $typed = $this->getNestedObject($path);
    if ($typed instanceof TypedDataInterface) {
      $return = $typed->getValue();
      return isset($return) ? $return : $default;
    }
  }

  public function getNestedString($path, $default = NULL) {
    $typed = $this->getNestedObject($path);
    if ($typed instanceof TypedDataInterface) {
      $return = $typed->getString();
      return isset($return) ? $return : $default;
    }
  }

  public function getNestedObject($path) {
    if (!isset($this->current) || (string)$path === '') {
      return $this->current;
    }
    if ($this->current) {
      $key = static::shift($path);
      try {
        $next = $this->current->get($key);
        if ($next instanceof DataReferenceInterface) {
          $next = static::dereference($next);
        }
        $return = (new static($next))->getNestedObject($path);
        return $return;
      } catch (\Throwable $e) {
      }
    }
  }

  protected static function dereference(DataReferenceInterface $next) {
    $next = $next->getTarget();
    return $next;
  }

  protected static function shift(&$path) {
    $parents = explode('/', $path);
    $key = array_shift($parents);
    $path = implode('/', $parents);
    return $key;
  }
}
