<?php

namespace Drupal\client_connection\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Client Connection item annotation object.
 *
 * @see \Drupal\client_connection\ClientConnectionManager
 * @see plugin_api
 *
 * @Annotation
 */
class ClientConnection extends Plugin {

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

  /**
   * The administrative description of the service.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The categories in the admin UI where the service will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation[]
   *
   * @ingroup plugin_translatable
   */
  public $categories = [];

}
