<?php

namespace Drupal\nimbus;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the language manager service.
 */
class NimbusServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    if ($container->has('drush.command.services')) {
      try {
        $definition = $container->getDefinition('drush.command.services');
        $method_calls = $definition->getMethodCalls();

        foreach ($definition->getMethodCalls() as $id => $call) {
          if (isset($call[0]) && isset($call[1]) && $call[0] == 'addCommandReference') {
            $value = $call[1][0];
            if (((string) $value) == 'config.import.commands') {
              $method_calls[$id][1] = [new Reference('nimbus.import_commands')];
            }

            if (((string) $value) == 'config.export.commands') {
              $method_calls[$id][1] = [new Reference('nimbus.export_commands')];
            }
          }
        }

        $definition->setMethodCalls($method_calls);

      }
      catch (\Exception $e) {

      }
    }

    if ($container->has('config.storage.staging')) {
      try {
        $definition = $container->getDefinition('config.storage.staging');
        $definition->setFactory('Drupal\nimbus\config\FileStorageFactoryAlter::getSync');
      }
      catch (\Exception $e) {

      }
    }
  }

}
