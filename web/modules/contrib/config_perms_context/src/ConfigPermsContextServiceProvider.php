<?php

namespace Drupal\config_perms_context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the plugin.manager.menu.local_task service.
 */
class ConfigPermsContextServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('config_perms.access_checker');
    $definition->setClass('Drupal\config_perms_context\Access\ConfigPermsAccessCheck')
      ->addArgument(new Reference('current_user'))
      ->addArgument(new Reference('entity_type.manager'));
  }

}
