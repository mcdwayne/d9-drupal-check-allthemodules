<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Annotation\VarnishCacheableEntity.
 */

namespace Drupal\adv_varnish\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines cacheable entity item annotation object.
 *
 * Plugin Namespace: Plugin\adv_varnish\VarnishCacheableEntity.
 *
 * @see \Drupal\config_pages\Plugin\IcecreamManager
 * @see plugin_api
 *
 * @Annotation
 */
class VarnishCacheableEntity extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Entity id.
   *
   * @var string
   */
  public $entityId;

  /**
   * Has bundles.
   *
   * @var boolean
   */
  public $perBunleSettings;

}
