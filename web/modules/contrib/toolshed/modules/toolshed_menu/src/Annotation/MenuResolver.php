<?php

namespace Drupal\toolshed_menu\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the Toolshed MenuResolver plugins.
 *
 * Toolshed MenuResolvers are classes that implement a rule for determining
 * which menu item is considered to be active. Initially this was created
 * to work with the Toolshed Menu Navigation block.
 *
 * Plugin Namespace: Plugin\Toolshed\MenuResolver
 *
 * @see \Drupal\Component\Annotation\Plugin
 * @see \Drupal\toolshed_menu\MenuResolver\MenuResolverInterface
 * @see \Drupal\toolshed_menu\MenuResolver\MenuResolverBase
 * @see \Drupal\toolshed_menu\MenuResolver\MenuResolverPluginManager
 *
 * @ingroup toolshed_menu_resolver_plugins
 *
 * @Annotation
 */
class MenuResolver extends Plugin {

  /**
   * The menu resolver ID / machine name.
   *
   * @var string
   */
  public $id;

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

}
