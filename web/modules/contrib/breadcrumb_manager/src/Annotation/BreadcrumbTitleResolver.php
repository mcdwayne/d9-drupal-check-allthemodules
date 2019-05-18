<?php

namespace Drupal\breadcrumb_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Breadcrumb title resolver item annotation object.
 *
 * @see \Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverManager
 * @see plugin_api
 *
 * @Annotation
 */
class BreadcrumbTitleResolver extends Plugin {

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

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Whether or not the title resolver is enabled.
   *
   * @var bool
   */
  public $enabled = TRUE;

}
