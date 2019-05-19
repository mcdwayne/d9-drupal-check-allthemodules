<?php

namespace Drupal\toolshed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the Toolshed third party settings plugins for entity configurations.
 *
 * Plugin Namespace: Plugin\Toolshed\ThirdPartyFormElements.
 *
 * @see \Drupal\Component\Annotation\Plugin
 * @see \Drupal\toolshed\ThirdPartyFormElementsInterface
 * @see \Drupal\toolshed\ThirdPartyFormElementsPluginManager
 *
 * @ingroup toolshed_third_party_form_elements_plugins
 *
 * @Annotation
 */
class ThirdPartyFormElements extends Plugin {

  /**
   * The globally unique plugin ID / machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the setting the plugin builds the form elements for.
   *
   * @var string
   */
  public $name;

  /**
   * The human friendly name for admin configuration forms.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The human friendly name for admin configuration forms.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $help;

  /**
   * An array of the ConfigEntityInterfaces ID that this plugin works with.
   *
   * @var string[]
   */
  public $entityTypes;

}
