<?php

namespace Drupal\flexiform\FormElement;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base Class for Flexiform Form Elements.
 */
abstract class FormElementBase extends PluginBase implements FormElementInterface {
  use StringTranslationTrait;
  use FormElementBaseTrait;

}
