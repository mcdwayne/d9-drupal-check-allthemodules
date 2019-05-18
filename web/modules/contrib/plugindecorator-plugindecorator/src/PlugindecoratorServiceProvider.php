<?php

namespace Drupal\plugindecorator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorates all plugin managers.
 *
 * Note that "decorator" must be lowercase here so core finds this.
 */
class PlugindecoratorServiceProvider extends ServiceProviderBase {

  /**
   * Alter service definitions.
   *
   * @throws PlugindecoratorServiceProviderException
   */
  public function alter(ContainerBuilder $container) {
    $serviceTags = $container->findTaggedServiceIds('plugindecorator');
    foreach ($serviceTags as $serviceId => $tags) {
      foreach ($tags as $tag) {
        foreach ($tag as $key => $id) {
          if ($key = 'manager') {
            $manager = "plugin.manager.$id";
            if ($container->has($manager)) {
              $this->decorate($container, $manager);
            }
            else {
              throw new PlugindecoratorServiceProviderException(
                sprintf('Invalid plugin manager id %s in service %s.', $id, $serviceId)
              );
            }
          }
          else {
            throw new PlugindecoratorServiceProviderException(
              sprintf('Invalid property %s="%s" in service %s.', $key, $id, $serviceId)
            );
          }
        }
      }
    }
  }

  /**
   * Decorate a plugin manager service.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param string $manager
   *   The plugin id.
   */
  private function decorate(ContainerBuilder $container, $manager) {
    $container->register("$manager.plugindecorator", PluginManagerDecorator::class)
      ->addArgument(new Reference("$manager.plugindecorator.inner"))
      ->addArgument(new Reference("plugin.manager.plugindecorator"))
      ->setDecoratedService($manager)
      ->setPublic(FALSE);
  }

}
