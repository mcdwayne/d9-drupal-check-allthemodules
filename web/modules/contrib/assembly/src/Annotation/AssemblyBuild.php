<?php

namespace Drupal\assembly\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Assembly build item annotation object.
 *
 * @see \Drupal\assembly\Plugin\AssemblyBuildManager
 * @see plugin_api
 *
 * @Annotation
 */
class AssemblyBuild extends Plugin {


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

  /**
   * A list of assembly types to which this build plugin applies
   * @var array
   */
  public $types;

}
