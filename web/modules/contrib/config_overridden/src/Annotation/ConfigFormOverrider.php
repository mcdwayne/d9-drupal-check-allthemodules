<?php

namespace Drupal\config_overridden\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a plugin annotation object.
 *
 * @Annotation
 */
class ConfigFormOverrider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the sms service plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The default weight of the language negotiation plugin.
   *
   * @var int
   */
  public $weight;

}
