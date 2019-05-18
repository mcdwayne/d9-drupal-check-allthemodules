<?php

namespace Drupal\micon_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class MiconMenuServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides plugin.manager.menu.link class so that we can save the icon
    // to the menu item options.
    $definition = $container->getDefinition('plugin.manager.menu.link');
    $definition->setClass('Drupal\micon_menu\MiconMenuLinkManager');

    // Overrides menu_link.static.overrides service so that we can save
    // options statically.
    $definition = $container->getDefinition('menu_link.static.overrides');
    $definition->setClass('Drupal\micon_menu\MiconStaticMenuLinkOverrides');
  }

}
