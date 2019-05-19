<?php

namespace Drupal\social_hub\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a platform annotation object.
 *
 * Plugin Namespace: Plugin/SocialHub/PlatformIntegration.
 *
 * @see \Drupal\social_hub\PlatformIntegrationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class PlatformIntegration extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the platform type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the platform.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The platform configurations for each mode.
   *
   * @var array
   */
  public $configuration;

}
