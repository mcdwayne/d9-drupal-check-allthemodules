<?php

namespace Drupal\adsense\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an adsense ad item annotation object.
 *
 * Plugin Namespace: Plugin\adsense\AdsenseAd.
 *
 * @see \Drupal\adsense\AdsenseAdManager
 * @see plugin_api
 *
 * @Annotation
 */
class AdsenseAd extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the ad type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Indicates if the plugin is for AdSense for Search.
   *
   * @var bool
   */
  public $isSearch;

  /**
   * Indicates if the plugin needs an Ad ID provided by the AdSense UI.
   *
   * @var bool
   */
  public $needsSlot;

}
