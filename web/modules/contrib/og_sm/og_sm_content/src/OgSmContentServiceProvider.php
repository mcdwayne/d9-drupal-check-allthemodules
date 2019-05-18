<?php

namespace Drupal\og_sm_content;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the 'og_sm.content.path_processor' service if og_sm_path exists.
 */
class OgSmContentServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['og_sm_path'])) {
      $service_definition = new Definition('Drupal\og_sm_content\PathProcessor\SiteContentPathProcessor', [
        new Reference('og_sm.path.site_path_manager'),
        new Reference('og_sm.site_manager'),
      ]);
      $service_definition->addTag('path_processor_inbound');
      $service_definition->addTag('path_processor_outbound');
      $container->setDefinition('og_sm.content.path_processor', $service_definition);
    }
  }

}
