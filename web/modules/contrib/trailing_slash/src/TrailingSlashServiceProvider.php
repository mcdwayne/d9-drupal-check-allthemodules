<?php

namespace Drupal\trailing_slash;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TrailingSlashServiceProvider.
 *
 * @package Drupal\trailing_slash
 */
class TrailingSlashServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if (
          $container->hasDefinition('url_generator.non_bubbling')
      &&  $container->hasDefinition('path_processor_language')
    ) {
      $container->getDefinition('url_generator.non_bubbling')
                ->setClass('Drupal\trailing_slash\Routing\TrailingSlashUrlGenerator')
                ->addArgument(new Reference('path_processor_language'));
    }
  }

}
