<?php

namespace Drupal\rokka\StyleEffects;

use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectRokkaTrim implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $options = [
      'fuzzy' => static::normalizePercent($data['fuzzy']),
    ];
    return [new StackOperation('trim', $options)];
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  protected static function normalizePercent($value) {
    $value = $value ? $value : 0;
    return min(100, max(0, $value));
  }

}
