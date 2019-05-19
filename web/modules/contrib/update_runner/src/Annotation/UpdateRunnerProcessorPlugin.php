<?php

namespace Drupal\update_runner\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Update Runner processor plugin item annotation object.
 *
 * @see \Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class UpdateRunnerProcessorPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
