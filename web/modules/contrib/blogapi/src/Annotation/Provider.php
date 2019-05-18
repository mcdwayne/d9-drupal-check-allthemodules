<?php

namespace Drupal\blogapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flavor item annotation object.
 *
 * Plugin Namespace: Plugin\blogapi\BlogapiProvider.
 *
 * @see \Drupal\blogapi\BlogapiProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class Provider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
