<?php

namespace Drupal\ad_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for Advertising view handler plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AdView extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Advertising view handler.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The library which contains the JS implementation for this view handler.
   *
   * @var string
   */
  public $library;

  /**
   * Whether the JS handler requires the document to be ready before execution.
   *
   * @var bool
   */
  public $requiresDomready;

  /**
   * A container which wraps or holds the output of this view handler.
   *
   * For regular HTML ads, the value 'html' can be used.
   * Implementations for other protocols might be different and
   * incompatible with the 'html' container.
   * In that case, use a value of your choice,
   * which can be used and identified later during theme processing.
   * You might need to override the template ad-entity.html.twig too.
   *
   * @var string
   */
  public $container;

  /**
   * A list of Advertising types the view handler is compatible with.
   *
   * @var string[]
   */
  public $allowedTypes;

}
