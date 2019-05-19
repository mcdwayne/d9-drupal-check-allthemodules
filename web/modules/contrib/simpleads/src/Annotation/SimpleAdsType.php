<?php

namespace Drupal\simpleads\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SimpleAdsType annotation object.
 *
 * Plugin Namespace: Plugin\simpleads\SimpleAdsType
 *
 * @see \Drupal\simpleads\Plugin\SimpleAdsManager
 * @see plugin_api
 *
 * @Annotation
 */
class SimpleAdsType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Ad type name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
