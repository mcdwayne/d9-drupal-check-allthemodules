<?php

namespace Drupal\wsdata\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Wsconnector plugin item annotation object.
 *
 * @see \Drupal\wsdata\Plugin\WSConnectorManager
 * @see plugin_api
 *
 * @Annotation
 */
class WSConnector extends Plugin {


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

}
