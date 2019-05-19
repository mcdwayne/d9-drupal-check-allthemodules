<?php

namespace Drupal\tracking_number\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tracking number type annotation object.
 *
 * @see \Drupal\tracking_number\Plugin\TrackingNumberTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class TrackingNumberType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label for the tracking number type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
