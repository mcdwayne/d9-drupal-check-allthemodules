<?php

namespace Drupal\fac\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a search annotation object.
 *
 * Plugin Namespace: Plugin\fac\Search.
 *
 * @see \Drupal\fac\SearchPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Search extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the search plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
