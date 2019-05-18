<?php

namespace Drupal\gapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GoogleApiServiceProvider annotation object.
 *
 * Plugin Namespace: Plugin\gapi\ServiceProvider
 *
 * @see \Drupal\gapi\Plugin\GoogleApiServiceProviderInterface
 * @see plugin_api
 *
 * @Annotation
 */
class GoogleApiServiceProvider extends Plugin {

  /**
   * The unique id of the service provider.
   *
   * @var string
   */
  public $id;

  /**
   * A descriptive, human-readable label for the service provider.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
