<?php

namespace Drupal\global_gateway\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a region negotiation annotation object.
 *
 * Plugin Namespace: Plugin\LanguageNegotiation.
 *
 * For a working example, see
 * \Drupal\region\Plugin\LanguageNegotiation\LanguageNegotiationBrowser.
 *
 * @see \Drupal\region\LanguageNegotiator
 * @see \Drupal\region\LanguageNegotiationMethodManager
 * @see \Drupal\region\LanguageNegotiationMethodInterface
 * @see hook_region_negotiation_info_alter()
 * @see plugin_api
 *
 * @Annotation
 */
class RegionNegotiation extends Plugin {

  /**
   * The region negotiation plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The default weight of the region negotiation plugin.
   *
   * @var int
   */
  public $weight;

  /**
   * The human-readable name of the region negotiation plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the region negotiation plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The route pointing to the plugin's configuration page.
   *
   * @var string
   */
  public $config_route_name;

}
