<?php

namespace Drupal\menu_per_user;
 
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
 
class MenuPerUserServiceProvider implements ServiceModifierInterface{
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('menu.default_tree_manipulators')
      ->setClass(MenuPerUserLinkTreeManipulator::class);
  }
}