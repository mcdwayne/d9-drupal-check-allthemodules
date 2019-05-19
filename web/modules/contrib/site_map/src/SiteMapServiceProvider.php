<?php

namespace Drupal\site_map;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * ServiceProvider class for site_map module.
 */
class SiteMapServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Change class of menu.link_tree service.
    $definition = $container->getDefinition('menu.link_tree');
    $definition->setClass('Drupal\site_map\Menu\MenuLinkTree');
  }

}
