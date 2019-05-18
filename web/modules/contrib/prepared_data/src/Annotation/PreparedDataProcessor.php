<?php

namespace Drupal\prepared_data\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation definition class for data processor plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class PreparedDataProcessor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Processor plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The execution weight of this processor (larger means later execution).
   *
   * @var int
   */
  public $weight;

  /**
   * Whether the plugin can be enabled by an administrator.
   *
   * @var bool
   */
  public $manageable;

}
