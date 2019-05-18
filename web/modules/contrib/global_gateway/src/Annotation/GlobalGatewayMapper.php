<?php

namespace Drupal\global_gateway\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Global Gateway Mapper annotation object.
 *
 * @see \Drupal\global_gateway\Mapper\MapperPluginManager
 * @see \Drupal\global_gateway\Mapper\MapperInterface
 * @see \Drupal\global_gateway\Mapper\MapperPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class GlobalGatewayMapper extends Plugin {

  /**
   * The mapper plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the mapper plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The mapper description.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
