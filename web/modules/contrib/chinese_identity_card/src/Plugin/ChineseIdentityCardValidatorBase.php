<?php

namespace Drupal\chinese_identity_card\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Chinese identity card validator plugins.
 */
abstract class ChineseIdentityCardValidatorBase extends PluginBase implements ChineseIdentityCardValidatorInterface {
  /**
   * @inheritdoc
   */
  abstract public function validate($value);

}
