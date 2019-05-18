<?php

namespace Drupal\global_gateway\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Global Gateway SwitcherData annotation object.
 *
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataPluginManager
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataInterface
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class GlobalGatewaySwitcherData extends Plugin {

  /**
   * The mapper plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
