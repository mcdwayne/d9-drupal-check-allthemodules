<?php

namespace Drupal\commerce_xero\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Describes the annotation for a Commerce Xero processor plugin.
 *
 * @Annotation
 */
class CommerceXeroProcessor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The data types that this plugin handles.
   *
   * @var array
   */
  public $types;

  /**
   * The execution state that this plugin will fire from.
   *
   * This can be one of the following values:
   *   - immediate: process the data type as it is created.
   *   - process: process the data type in the "process" queue.
   *   - send: process the data type in the "send" queue before it is sent.
   *
   * @var string
   */
  public $execution;

  /**
   * The plugin default settings.
   *
   * @var array
   */
  public $settings;

  /**
   * Makes a plugin required on any strategy.
   *
   * @var bool
   */
  public $required;

}
