<?php

namespace Drupal\elevatezoomplus;

use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings.
 */
class ElevateZoomPlusDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'elevatezoomplus' => 'default',
    ] + parent::baseSettings();
  }

  /**
   * Returns fieldable entity formatter and Views settings.
   */
  public static function extendedSettings() {
    return self::baseSettings() + parent::extendedSettings();
  }

}
