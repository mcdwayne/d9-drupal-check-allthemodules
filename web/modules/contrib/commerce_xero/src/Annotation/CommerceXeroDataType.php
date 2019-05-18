<?php

namespace Drupal\commerce_xero\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Describes the annotation for a Commerce Xero data_type processor plugin.
 *
 * @Annotation
 */
class CommerceXeroDataType extends Plugin {

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
   * The data type.
   *
   * @var string
   */
  public $type;

  /**
   * The plugin default settings.
   *
   * @var array
   */
  public $settings;

}
