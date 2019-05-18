<?php

namespace Drupal\domain_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class MmDomainRoleServiceProvider.
 *
 * Allows overriding DI container definitions whenever the module is
 * enabled (instead of having to add it to services.yml):
 *  - Change the class for current_user service.
 *
 * @package Drupal\mm_domain_role
 */
class DomainMenuServiceProvider extends ServiceProviderBase {

  /**
   * Overrides the class used for providing the "Menu Parent" drop-down.
   *
   * @inheritdoc
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('menu.parent_form_selector')->setClass('\Drupal\domain_menu\DomainMenuParentFormSelector');
  }

}
