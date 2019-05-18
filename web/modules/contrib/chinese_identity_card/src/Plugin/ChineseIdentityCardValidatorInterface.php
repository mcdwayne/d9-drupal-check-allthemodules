<?php

namespace Drupal\chinese_identity_card\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Chinese identity card validator plugins.
 */
interface ChineseIdentityCardValidatorInterface extends PluginInspectionInterface {

  /**
   * Validate the id number.
   *
   * @param $value string
   *
   * @return boolean
   */
  public function validate($value);
}
