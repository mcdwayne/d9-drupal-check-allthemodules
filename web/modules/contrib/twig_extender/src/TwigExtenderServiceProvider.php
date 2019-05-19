<?php

namespace Drupal\twig_extender;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the language manager service.
 */
class TwigExtenderServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('twig.extension');
    $definition->setClass('\Drupal\twig_extender\TwigExtenderService');
    $definition->addArgument(
      new Reference('plugin.manager.twig_extender')
    );
  }

}
