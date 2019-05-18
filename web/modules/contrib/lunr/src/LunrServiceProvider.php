<?php

namespace Drupal\lunr;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\lunr\EventSubscriber\TomePathSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services in the container.
 */
class LunrServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['tome_static'])) {
      $container->register('lunr.tome_path_subscriber', TomePathSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'));
    }
  }

}
