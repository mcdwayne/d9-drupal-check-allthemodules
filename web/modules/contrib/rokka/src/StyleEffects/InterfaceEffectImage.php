<?php

namespace Drupal\rokka\StyleEffects;

/**
 *
 */
interface InterfaceEffectImage {

  /**
   * @param $data
   *
   * @return \Rokka\Client\Core\StackOperation[]
   */
  public static function buildRokkaStackOperation($data);

}
