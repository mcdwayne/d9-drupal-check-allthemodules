<?php

namespace Drupal\janrain_connect_ui\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A base class to define standard operations of a login constraint.
 */
abstract class LoginConstraintBase extends PluginBase implements LoginConstraintInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->pluginDefinition['errorMessage'];
  }

}
