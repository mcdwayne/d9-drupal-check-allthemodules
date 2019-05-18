<?php

namespace Drupal\hn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a HN Path resolver item annotation object.
 *
 * @see \Drupal\hn\Plugin\HnPathResolverManager
 * @see plugin_api
 *
 * @Annotation
 */
class HnPathResolver extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The priority of the plugin.
   *
   * @var int
   */
  public $priority = 0;

}
