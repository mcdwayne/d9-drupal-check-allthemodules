<?php

/**
 * @file
 * Provides Drupal\themekey\PropertyBase.
 */

namespace Drupal\themekey;

use Drupal\themekey\Plugin\SingletonPluginBase;

abstract class PropertyAdminDigitBase extends SingletonPluginBase implements PropertyAdminInterface {

  /**
   * {@inheritdoc}
   */
  public function validateFormat($value) {
    return ctype_digit($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElement() {
    return array();
  }

}

