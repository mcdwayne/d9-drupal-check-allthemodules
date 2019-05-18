<?php

namespace Drupal\adva\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for access providers.
 *
 * Plugin Namespace: Plugin\adva\AccessProvider.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AccessProvider extends Plugin {

  /**
   * The provider id.
   *
   * @var string
   */
  public $id;

  /**
   * The provider label.
   *
   * @var string
   */
  public $label;

}
