<?php

namespace Drupal\depcalc;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Optionally adds to the container when content_moderation is enabled.
 */
class DepcalcServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    if ($container->hasDefinition('content_moderation.moderation_information')) {
      $container->getDefinition('workflow.dependency_calculator')
        ->addArgument(new Reference('content_moderation.moderation_information'));
    }

    if ($container->hasDefinition('plugin.manager.core.layout')) {
      $container->getDefinition('layout_builder.dependency_calculator')
        ->addArgument(new Reference('plugin.manager.core.layout'));
    }
  }

}

