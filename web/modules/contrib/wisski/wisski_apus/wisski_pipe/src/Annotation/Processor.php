<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Annotation\Processor.
 */

namespace Drupal\wisski_pipe\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a processor annotation object.
 *
 * Plugin Namespace: Plugin\wisski_pipe\Processor
 *
 * @see \Drupal\wisski_pipe\ProcessorInterface
 * @see \Drupal\wisski_pipe\ProcessorBase
 * @see \Drupal\wisski_pipe\ProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class Processor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the processor.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;


  /**
   * A description of the processor functionality.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;
  
  
  /**
   * An array of tags that help categorize the processor.
   * 
   * @var string[]
   */
  public $tags;

}
