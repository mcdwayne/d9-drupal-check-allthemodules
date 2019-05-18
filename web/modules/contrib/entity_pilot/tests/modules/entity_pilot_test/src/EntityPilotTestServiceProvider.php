<?php

namespace Drupal\entity_pilot_test;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates a service modifier to hijack the transport service.
 */
class EntityPilotTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($transport = $container->getDefinition('entity_pilot.transport')) {
      $transport->setClass('Drupal\entity_pilot_test\MockTransport');
      $transport->setArguments([new Reference('state')]);
      $container->setDefinition('entity_pilot.transport', $transport);
    }
  }

}
