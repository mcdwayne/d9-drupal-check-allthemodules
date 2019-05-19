<?php

namespace Drupal\simpleads\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SimpleAdsCampaign annotation object.
 *
 * Plugin Namespace: Plugin\simpleads\SimpleAdsCampaign
 *
 * @see \Drupal\simpleads\Plugin\SimpleAdsManager
 * @see plugin_api
 *
 * @Annotation
 */
class SimpleAdsCampaign extends Plugin {

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
