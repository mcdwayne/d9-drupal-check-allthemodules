<?php

/**
 * @file
 * Contains \Drupal\streamy\Annotation\StreamyStream.
 */

namespace Drupal\streamy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a StreamyStream item annotation object.
 *
 * Plugin Namespace: streamy\Plugin\StreamyStream
 *
 * @see \Drupal\streamy\StreamyStreamManager
 * @see plugin_api
 *
 * @Annotation
 */
class StreamyStream extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The prefix of the configuration used by the current plugin.
   */
  public $configPrefix;

  /**
   * The human-readable name of the action plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * The human-readable name of the action plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;
}
