<?php

namespace Drupal\apitools\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Client annotation item annotation object.
 *
 * TODO:
 * - Add a definition key for environments this client will be used
 * - this will create a key form for each environment, and then a switch
 * @see \Drupal\apitools\ClientManager
 * @see plugin_api
 *
 * @Annotation
 */
class ApiToolsClient extends Plugin {


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
