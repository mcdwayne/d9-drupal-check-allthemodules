<?php

namespace Drupal\druminate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Convio Endpoint item annotation object.
 *
 * @see \Drupal\druminate\Plugin\ConvioEndpointManager
 * @see plugin_api
 *
 * @Annotation
 */
class DruminateEndpoint extends Plugin {

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
   * The Api Servlet.
   *
   * @var string
   */
  public $servlet;

  /**
   * The Api Method.
   *
   * @var string
   */
  public $method;

  /**
   * Determines whether or not an auth token should be added to request.
   *
   * @var bool
   */
  public $authRequired;

  /**
   * Determines the amount of time in seconds the data should be stored.
   *
   * @var int
   */
  public $cacheLifetime;

  /**
   * The Api Method.
   *
   * @var array
   */
  public $params;

  /**
   * The Api Url (optional).
   *
   * This parameter is used the consume non-convio urls.
   *
   * @var string
   */
  public $customUrl;

}
