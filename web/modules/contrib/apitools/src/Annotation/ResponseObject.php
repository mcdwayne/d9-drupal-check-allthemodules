<?php

namespace Drupal\apitools\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ResponseObject annotation object.
 *
 * @see \Drupal\apitools\ResponseObjectManager
 * @see plugin_api
 *
 * @Annotation
 */
class ResponseObject extends Plugin {

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

  public $baseEntityType;

}
