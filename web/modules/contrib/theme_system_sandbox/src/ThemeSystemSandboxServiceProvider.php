<?php

/**
 * @file
 * Contains \Drupal\theme_system_sandbox\ThemeSystemSandboxServiceProvider.
 */

namespace Drupal\theme_system_sandbox;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service provider for the theme_system_sandbox module.
 */
class ThemeSystemSandboxServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('renderer')->setClass('Drupal\theme_system_sandbox\Render\Renderer');
    $container->getDefinition('theme.initialization')->setClass('Drupal\theme_system_sandbox\Theme\ThemeInitialization');
  }

}
