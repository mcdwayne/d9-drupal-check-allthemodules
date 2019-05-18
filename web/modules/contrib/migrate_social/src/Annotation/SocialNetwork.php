<?php

namespace Drupal\migrate_social\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SocialNetwork annotation object.
 *
 * @see \Drupal\migrate_social\SocialNetworkPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class SocialNetwork extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * A brief, human readable, description of the plugin type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
