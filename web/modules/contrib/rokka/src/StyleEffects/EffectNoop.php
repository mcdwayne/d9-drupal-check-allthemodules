<?php

namespace Drupal\rokka\StyleEffects;

use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectNoop implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    return [new StackOperation('noop', [])];
  }

}
