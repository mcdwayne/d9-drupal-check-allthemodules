<?php

namespace Drupal\entity_pilot_git;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates a service modifier to hijack the transport service.
 */
class EntityPilotGitServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if (isset($modules['entity_pilot']) && $transport = $container->getDefinition('entity_pilot.transport')) {
      $transport->setClass('Drupal\entity_pilot_git\GitTransport');
      $transport->setArguments(
        [
          new Reference('serialization.json'),
          new Reference('config.factory'),
          new Reference('hal.link_manager.type'),
          new Reference('entity_type.manager'),
        ]
      );
      $container->setDefinition('entity_pilot.transport', $transport);
    }
  }

}
