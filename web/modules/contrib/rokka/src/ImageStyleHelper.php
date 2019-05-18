<?php

namespace Drupal\rokka;

/**
 *
 */
class ImageStyleHelper {

  /**
   * Returns the Angle value in [0-360] interval.
   *
   * @param $angle
   *
   * @return int
   */
  public static function operationNormalizeAngle($angle) {
    $angle = $angle % 360;
    if ($angle < 0) {
      $angle = 360 + $angle;
    }
    return $angle;
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  public static function operationNormalizeColor($value) {
    return str_replace('#', '', $value);
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  public static function operationNormalizeSize($value) {
    $value = $value ? $value : PHP_INT_MAX;
    return min(10000, max(1, $value));
  }

  /**
   * @param array $effects
   *
   * @return \Rokka\Client\Core\StackOperation[]
   */
  public static function buildStackOperationCollection($effects) {
    if (empty($effects)) {
      $effects = [
        [
          'name' => 'noop',
          'data' => NULL,
        ],
      ];
    }

    $operations = [];
    $currentId = 0;
    foreach ($effects as $effect) {
      $ops = static::buildStackOperation($effect);
      if (!empty($ops)) {
        foreach ($ops as $op) {
          $operations[$currentId++] = $op;
        }
      }
    }

    if (empty($operations)) {
      return NULL;
    }

    ksort($operations);
    return $operations;
  }

  /**
   * @param array $effect
   *
   * @return \Rokka\Client\Core\StackOperation[]
   */
  public static function buildStackOperation(array $effect) {
    $name = $effect['name'];
    $className = 'Drupal\rokka\StyleEffects\Effect' . static::camelCase($name, TRUE);

    $ret = [];
    if (class_exists($className) && in_array('Drupal\rokka\StyleEffects\InterfaceEffectImage', class_implements($className))) {
      /** @var \Drupal\rokka\StyleEffects\InterfaceEffectImage $className */
      $ret = $className::buildRokkaStackOperation($effect['data']);
    }
    else {
      watchdog('rokka', 'Can not convert effect "%effect" to Rokka.io StackOperation: "%class" Class missing!', [
        '%effect' => $name,
        '%class' => $className,
      ]);
    }

    return $ret;
  }

  /**
   * @param string $str
   *
   * @return string
   */
  public static function camelCase($str, $classCase = FALSE) {
    // non-alpha and non-numeric characters become spaces.
    $str = preg_replace('/[^a-z0-9]+/i', ' ', $str);
    $str = trim($str);
    // Uppercase the first character of each word.
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);
    if (!$classCase) {
      $str = lcfirst($str);
    }
    return $str;
  }

}
