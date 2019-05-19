<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Annotation\Engine.
 */

namespace Drupal\wisski_salz\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an adapter engine annotation object
 *
 * @see \Drupal\wisski_salz\EngineManager
 * @see plugin_api
 *
 * @Annotation
 */
class Engine extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of the engine.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * A description of the engine.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;



}
