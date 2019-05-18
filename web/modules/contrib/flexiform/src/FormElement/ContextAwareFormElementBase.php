<?php

namespace Drupal\flexiform\FormElement;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base Class for Flexiform Form Elements.
 */
abstract class ContextAwareFormElementBase extends ContextAwarePluginBase implements FormElementInterface {
  use StringTranslationTrait;
  use FormElementBaseTrait;

}
