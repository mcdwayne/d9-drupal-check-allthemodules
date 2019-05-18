<?php

namespace Drupal\config_ignore_keys\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Configuration ignore key plugin.
 *
 * @Annotation
 *
 * @Attributes({
 * @Attribute("id", required = true, type = "string"),
 * @Attribute("description", required = false, type = "string"),
 * })
 */
class ConfigurationIgnorePlugin extends Plugin {

  /**
   * The id of the plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The description for the plugin.
   *
   * @var string
   */
  public $description;

}
