<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\ServiceProvider.
 */

namespace Drupal\menu_link_weight;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\menu_link_weight\MenuParentFormSelector\CshsMenuParentFormSelector;

/**
 * Overrides the menu.parent_form_selector service.
 */
class MenuLinkWeightServiceProvider extends ServiceProviderBase {

  /**
   * @inheritDoc
   */
  public function alter(ContainerBuilder $container) {
    $settings = BootstrapConfigStorageFactory::get()->read('menu_link_weight.settings');
    $modules = $container->getParameter('container.modules');
    if ($settings['menu_parent_form_selector'] === 'cshs' && isset($modules['cshs'])) {
      $defintion = $container->getDefinition('menu.parent_form_selector');
      $defintion->setClass(CshsMenuParentFormSelector::class);
    }
  }

}
